<?php

declare(strict_types=1);

use App\Enums\ColorScheme;
use App\Enums\EmailTemplate;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationFrequency;
use App\Enums\PermissionName;
use App\Models\ApplicationSettings;
use App\Models\Audit;
use App\Models\Client;
use App\Models\DefaultClientNotification;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\seed;

describe('the essential seeder', function (): void {
    beforeEach(function (): void {
        seed(DatabaseSeeder::class);
    });

    it('creates every application permission', function (): void {
        expect(Permission::query()->count())->toBe(count(PermissionName::cases()));
    });

    it('creates an administrator role holding every permission', function (): void {
        $role = Role::findByName((string) config('cadence.administrator_role'), 'web');

        expect($role->permissions)->toHaveCount(count(PermissionName::cases()));
    });

    it('creates a viewer role that can read everything and change nothing', function (): void {
        $viewer = Role::findByName('Viewer', 'web');

        $granted = $viewer->permissions->pluck('name');

        expect($granted)->not->toBeEmpty()
            ->and($granted->every(fn (string $name): bool => str_ends_with($name, '.view') || str_ends_with($name, '.viewAny')))->toBeTrue()
            ->and($granted)->toContain(PermissionName::ClientsViewAny->value)
            ->and($granted)->not->toContain(PermissionName::ClientsUpdate->value)
            ->and($granted)->not->toContain(PermissionName::ClientsExport->value);
    });

    it('creates one administrator who can administer access', function (): void {
        $administrator = User::query()->where('email', config('cadence.initial_administrator.email'))->firstOrFail();

        expect($administrator->is_active)->toBeTrue()
            ->and($administrator->can(PermissionName::UsersUpdate->value))->toBeTrue()
            ->and($administrator->can(PermissionName::RolesUpdate->value))->toBeTrue();
    });

    it('creates the application settings row', function (): void {
        expect(ApplicationSettings::query()->count())->toBe(1);
    });

    it('creates no demo business data at all', function (): void {
        expect(Client::query()->withTrashed()->count())->toBe(0)
            ->and(NotificationSchedule::query()->withTrashed()->count())->toBe(0)
            ->and(NotificationDelivery::query()->count())->toBe(0)
            ->and(DefaultClientNotification::query()->count())->toBe(0)
            ->and(Audit::query()->count())->toBe(0)
            ->and(DatabaseNotification::query()->count())->toBe(0)
            ->and(User::query()->count())->toBe(1);
    });

    it('can be run twice without creating anything twice', function (): void {
        seed(DatabaseSeeder::class);

        expect(User::query()->count())->toBe(1)
            ->and(Permission::query()->count())->toBe(count(PermissionName::cases()))
            ->and(ApplicationSettings::query()->count())->toBe(1);
    });
});

describe('the demo seeder', function (): void {
    beforeEach(function (): void {
        seed(DemoSeeder::class);
    });

    it('creates users at several permission levels, including a deactivated one', function (): void {
        // The demo data invents no roles of its own: it uses the two RoleSeeder ships.
        expect(User::query()->count())->toBeGreaterThanOrEqual(5)
            ->and(User::query()->where('is_active', false)->count())->toBeGreaterThanOrEqual(1)
            ->and(User::query()->whereDoesntHave('roles')->count())->toBeGreaterThanOrEqual(1)
            ->and(Role::query()->count())->toBeGreaterThanOrEqual(2)
            ->and(User::query()->whereHas('roles', fn ($role) => $role->where('name', 'Viewer'))->exists())->toBeTrue();
    });

    it('creates users with different themes', function (): void {
        expect(User::query()->where('color_scheme', ColorScheme::Dark->value)->exists())->toBeTrue()
            ->and(User::query()->where('color_scheme', ColorScheme::Light->value)->exists())->toBeTrue()
            ->and(User::query()->where('timezone', '!=', 'UTC')->exists())->toBeTrue();
    });

    it('creates active, inactive and archived clients, with and without notes', function (): void {
        expect(Client::query()->active()->count())->toBeGreaterThanOrEqual(3)
            ->and(Client::query()->where('status', 'inactive')->count())->toBeGreaterThanOrEqual(1)
            ->and(Client::onlyTrashed()->count())->toBeGreaterThanOrEqual(1)
            ->and(Client::query()->whereNotNull('notes')->count())->toBeGreaterThanOrEqual(1)
            ->and(Client::query()->whereNull('notes')->count())->toBeGreaterThanOrEqual(1);
    });

    it('creates every frequency and both schedule states', function (): void {
        foreach (NotificationFrequency::cases() as $frequency) {
            expect(NotificationSchedule::query()->where('frequency', $frequency->value)->exists())->toBeTrue();
        }

        expect(NotificationSchedule::query()->where('is_enabled', true)->exists())->toBeTrue()
            ->and(NotificationSchedule::query()->where('is_enabled', false)->exists())->toBeTrue()
            ->and(NotificationSchedule::query()->whereNotNull('last_sent_at')->exists())->toBeTrue();
    });

    it('creates notifications due today, soon and further out', function (): void {
        $now = CarbonImmutable::now();

        expect(NotificationSchedule::query()->due($now)->exists())->toBeTrue()
            ->and(NotificationSchedule::query()
                ->whereBetween('next_send_at', [$now, $now->addDays(7)])
                ->exists())->toBeTrue()
            ->and(NotificationSchedule::query()
                ->where('next_send_at', '>', $now->addDays(30))
                ->exists())->toBeTrue();
    });

    it('creates sent, failed and pending delivery history', function (): void {
        foreach (NotificationDeliveryStatus::cases() as $status) {
            expect(NotificationDelivery::query()->where('status', $status->value)->exists())->toBeTrue();
        }
    });

    it('creates schedules for both targets, including a blank one', function (): void {
        $lists = NotificationSchedule::query()->whereNull('client_id')->get();

        expect(NotificationSchedule::query()->whereNotNull('client_id')->exists())->toBeTrue()
            ->and($lists)->not->toBeEmpty()
            ->and($lists->every(fn (NotificationSchedule $schedule): bool => $schedule->name !== null))->toBeTrue()
            ->and($lists->contains(fn (NotificationSchedule $schedule): bool => $schedule->template === EmailTemplate::Blank))->toBeTrue()
            ->and($lists->contains(fn (NotificationSchedule $schedule): bool => $schedule->message !== null))->toBeTrue();
    });

    it('creates one delivery per address for a recipient list, including a rejected one', function (): void {
        $listDeliveries = NotificationDelivery::query()->whereNull('client_id')->where('is_manual', false)->get();

        expect($listDeliveries->count())->toBeGreaterThanOrEqual(2)
            ->and($listDeliveries->pluck('scheduled_for')->unique())->toHaveCount(1)
            ->and($listDeliveries->pluck('target_name')->unique())->toHaveCount(1)
            ->and($listDeliveries->where('status', NotificationDeliveryStatus::Failed)->count())->toBe(1);
    });

    it('creates a manual send alongside the scheduled ones', function (): void {
        $manual = NotificationDelivery::query()->manual()->first();

        expect($manual)->not->toBeNull()
            ->and($manual->notification_schedule_id)->toBeNull()
            ->and($manual->triggered_by_user_id)->not->toBeNull()
            ->and(NotificationDelivery::query()->where('is_manual', false)->exists())->toBeTrue();
    });

    it('creates read and unread application notifications', function (): void {
        expect(DatabaseNotification::query()->whereNull('read_at')->exists())->toBeTrue()
            ->and(DatabaseNotification::query()->whereNotNull('read_at')->exists())->toBeTrue();
    });

    it('creates audit entries including a system one', function (): void {
        expect(Audit::query()->whereNotNull('user_id')->exists())->toBeTrue()
            ->and(Audit::query()->whereNull('user_id')->exists())->toBeTrue();
    });

    it('creates default client notifications', function (): void {
        expect(DefaultClientNotification::query()->count())->toBeGreaterThanOrEqual(2);
    });

    it('can be run twice without duplicating anything', function (): void {
        $clients = Client::query()->withTrashed()->count();
        $schedules = NotificationSchedule::query()->count();
        $users = User::query()->count();

        seed(DemoSeeder::class);

        expect(Client::query()->withTrashed()->count())->toBe($clients)
            ->and(NotificationSchedule::query()->count())->toBe($schedules)
            ->and(User::query()->count())->toBe($users);
    });
});
