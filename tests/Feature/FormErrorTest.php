<?php

declare(strict_types=1);

use App\Enums\ClientAttributeType;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\DefaultClientNotification;
use App\Models\NotificationSchedule;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

/**
 * Validation messages are shown in exactly one way: `x-form.error`, under the thing that is
 * wrong. There is no summary box repeating them all in a second style.
 *
 * That only works if every message a form can produce has somewhere to appear. A rule on a
 * key with no field would fail silently — the form would come back unchanged with nothing
 * explaining why — so these tests put a message under every rule key each page can produce
 * and insist the page shows it. The keys come from the `rules()` of the Form Request that
 * receives each form.
 */
function guardMessage(string $key): string
{
    return "Cadence guard message for {$key}";
}

/**
 * Renders a page as though every one of these keys had failed validation.
 *
 * The bag is handed to the views directly. Seeding it through the session does not survive
 * the round trip, and a real failing submission cannot reach the keys that only a tampered
 * payload would trip.
 *
 * @param  list<string>  $keys
 */
function assertShowsEveryMessage(string $url, array $keys, ?User $actor = null): void
{
    $messages = [];

    foreach ($keys as $key) {
        $messages[$key] = [guardMessage($key)];
    }

    $bag = (new ViewErrorBag)->put('default', new MessageBag($messages));

    View::composer('*', fn ($view) => $view->with('errors', $bag));

    $response = ($actor === null ? test() : actingAs($actor))->get($url);

    $response->assertOk();

    $content = (string) $response->getContent();

    foreach ($keys as $key) {
        expect(str_contains($content, guardMessage($key)))->toBeTrue(
            "The [{$key}] message has nowhere to appear on {$url}, so that failure would be invisible."
        );
    }
}

it('shows every message the guest forms can produce', function (string $route, array $parameters, array $keys): void {
    assertShowsEveryMessage(route($route, $parameters), $keys);
})->with([
    'login' => ['login', [], ['email', 'password', 'remember']],
    'forgot password' => ['password.request', [], ['email']],
    'reset password' => ['password.reset', ['token' => 'a-token'], ['token', 'email', 'password']],
]);

it('shows every message the client forms can produce', function (): void {
    $administrator = administrator();
    $client = Client::factory()->create();
    DefaultClientNotification::factory()->create();

    // One of every type, so the generated keys below cover every branch of the partial.
    foreach (ClientAttributeType::cases() as $position => $type) {
        ClientAttribute::factory()
            ->when($type === ClientAttributeType::Repeater, fn ($factory) => $factory->nestedRepeater())
            ->when($type === ClientAttributeType::Select, fn ($factory) => $factory->select())
            ->ofType($type)
            ->create(['position' => $position]);
    }

    /*
    | The attribute keys depend on database rows, so they are read off the request rather
    | than written out here: a new one could otherwise ship with nowhere to appear.
    */
    $attributeKeys = array_keys(StoreClientRequest::create(route('clients.store'), 'POST')->clientAttributeRules());

    assertShowsEveryMessage(route('clients.create'), [
        'name', 'email', 'status', 'notes',
        'schedules', 'schedules.0.default_id', 'schedules.0.starts_at', 'schedules.0.is_enabled',
        ...$attributeKeys,
    ], $administrator);

    assertShowsEveryMessage(route('clients.edit', $client), [
        'name', 'email', 'status', 'notes', ...$attributeKeys,
    ], $administrator);
});

it('shows every message the schedule forms can produce', function (): void {
    $administrator = administrator();
    $client = Client::factory()->create();
    $schedule = NotificationSchedule::factory()->for($client)->create();
    $listSchedule = NotificationSchedule::factory()->forRecipients()->create();

    $shared = [
        'template', 'frequency', 'starts_at', 'is_enabled', 'subject', 'message',
        // Which blanks exist depends on the template chosen in the browser, so every one
        // of them reports above the rows rather than under a field of its own.
        'template_bindings', 'template_bindings.due_date.source', 'template_bindings.due_date.value',
    ];
    $listOnly = ['name', 'recipients', 'recipients.*'];

    // Only the general form chooses a target, so only it can fail on one.
    assertShowsEveryMessage(
        route('cadence.schedules.create'),
        [...$shared, ...$listOnly, 'target', 'client'],
        $administrator,
    );

    // These forms have their target already, so target and client are not theirs to fail on.
    assertShowsEveryMessage(route('clients.schedules.create', $client), $shared, $administrator);
    assertShowsEveryMessage(route('cadence.schedules.edit', $schedule), $shared, $administrator);
    assertShowsEveryMessage(route('cadence.schedules.edit', $listSchedule), [...$shared, ...$listOnly], $administrator);
});

it('shows every message the manual send form can produce', function (): void {
    Client::factory()->create();

    assertShowsEveryMessage(route('cadence.deliveries.send'), [
        'target', 'client', 'template', 'name', 'recipients', 'recipients.*', 'subject', 'message',
        'template_bindings', 'template_bindings.due_date.source', 'template_bindings.due_date.value',
    ], administrator());
});

it('shows every message the user and role forms can produce', function (): void {
    $administrator = administrator();
    $user = User::factory()->create();
    $role = Role::factory()->create();

    assertShowsEveryMessage(route('admin.users.create'), [
        'name', 'email', 'password', 'is_active', 'roles', 'roles.0',
    ], $administrator);

    assertShowsEveryMessage(route('admin.users.edit', $user), [
        'name', 'email', 'password', 'roles', 'roles.0',
    ], $administrator);

    assertShowsEveryMessage(route('admin.roles.create'), ['name', 'permissions', 'permissions.0'], $administrator);
    assertShowsEveryMessage(route('admin.roles.edit', $role), ['name', 'permissions', 'permissions.0'], $administrator);
});

it('shows every message the client attribute forms can produce', function (): void {
    $administrator = administrator();
    $attribute = ClientAttribute::factory()->repeater()->create();

    $definition = [
        'name', 'hint', 'options', 'is_required', 'is_active', 'position',
        'fields', 'fields.0.key', 'fields.0.name', 'fields.0.type', 'fields.0.is_required', 'fields.0.options',
        // The deepest level the editor draws, which is also the deepest it validates.
        'fields.0.fields.0.fields.0.name',
    ];

    assertShowsEveryMessage(route('admin.client-attributes.create'), [...$definition, 'type'], $administrator);

    // The type is settled at creation, so the edit form cannot fail on one.
    assertShowsEveryMessage(route('admin.client-attributes.edit', $attribute), $definition, $administrator);
});

it('shows every message the settings forms can produce', function (): void {
    $administrator = administrator();

    assertShowsEveryMessage(route('admin.settings.edit'), [
        'application_name', 'default_locale', 'default_timezone',
        'client_email_sender_name', 'client_email_sender_email',
    ], $administrator);

    assertShowsEveryMessage(route('admin.settings.edit'), [
        'entries', 'entries.0.template', 'entries.0.frequency', 'entries.0.is_enabled_by_default',
    ], $administrator);
});

it('shows every message the profile forms can produce', function (): void {
    assertShowsEveryMessage(route('profile.edit'), [
        'name', 'current_password', 'password', 'color_scheme', 'locale', 'timezone',
    ], administrator());
});

it('shows every message the upload step can produce', function (): void {
    Storage::fake('local');

    $administrator = administrator();

    assertShowsEveryMessage(route('clients.import.create'), ['file'], $administrator);
    assertShowsEveryMessage(route('admin.users.import.create'), ['file'], $administrator);
});

it('shows every message the mapping step can produce', function (): void {
    Storage::fake('local');

    $administrator = administrator();

    actingAs($administrator);

    post(route('clients.import.store'), [
        'file' => UploadedFile::fake()->createWithContent(
            'clients.csv',
            "Client name,Email address,Status\nMapped Studio,mapped@example.test,active\n"
        ),
    ])->assertRedirect(route('clients.import.mapping'));

    assertShowsEveryMessage(route('clients.import.mapping'), ['mapping', 'mapping.name'], $administrator);
});

it('renders validation state only through the shared components', function (): void {
    $offenders = [];

    $directory = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));

    /** @var SplFileInfo $file */
    foreach ($directory as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = (string) file_get_contents($file->getPathname());

        // Only the form components may touch validation state: `x-form.error` renders the
        // message, and the field components mark themselves invalid for assistive software.
        // Anywhere else means a second, divergent style.
        $touchesErrors = preg_match('/@error\b|\$errors\b/', $contents) === 1;
        $isFormComponent = str_contains($contents, 'aria-describedby')
            || str_ends_with($file->getPathname(), 'components/form/error.blade.php');

        if ($touchesErrors && ! $isFormComponent) {
            $offenders[] = str_replace(resource_path('views').'/', '', $file->getPathname());
        }
    }

    expect($offenders)->toBe([], 'These views render validation state themselves instead of using <x-form.error>: '.implode(', ', $offenders));
});
