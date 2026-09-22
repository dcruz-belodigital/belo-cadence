<?php

declare(strict_types=1);

use App\Actions\Notifications\SendScheduledNotificationAction;
use App\Enums\ClientAttributeType;
use App\Enums\EmailTemplate;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationFrequency;
use App\Enums\NotificationTarget;
use App\Mail\NotificationMail;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\ClientAttributeFile;
use App\Models\ClientAttributeValue;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use App\ValueObjects\TemplateAttachments;
use App\ValueObjects\TemplateBinding;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

/**
 * A notification may carry the files a client uploaded against a file attribute.
 *
 * The binding is the same attribute-and-path token a template value uses, so a file
 * inside repeating rows attaches one file per row — and what is sent is whatever the
 * client holds at that moment, never a copy taken when the schedule was written.
 */
beforeEach(function (): void {
    Mail::fake();
    Notification::fake();
    Storage::fake(ClientAttributeFile::DISK);
});

/**
 * @param  list<TemplateBinding>  $bindings
 */
function scheduleAttaching(Client $client, array $bindings): NotificationSchedule
{
    return NotificationSchedule::factory()->for($client)->create([
        'template' => EmailTemplate::GeneralReminder,
        'frequency' => NotificationFrequency::Yearly,
        'attachment_bindings' => new TemplateAttachments($bindings),
        'next_send_at' => CarbonImmutable::now(),
    ]);
}

function sendNow(NotificationSchedule $schedule): NotificationDelivery
{
    $schedule->load('client');

    app(SendScheduledNotificationAction::class)($schedule, $schedule->next_send_at);

    return NotificationDelivery::query()->latest('id')->firstOrFail();
}

/**
 * A client that has answered a plain file attribute.
 *
 * @return array{0: Client, 1: ClientAttribute, 2: ClientAttributeFile}
 */
function clientWithContract(): array
{
    $attribute = ClientAttribute::factory()->ofType(ClientAttributeType::File)->create(['name' => 'Signed contract']);
    $client = Client::factory()->create();

    $file = ClientAttributeFile::factory()->stored()->create([
        'client_id' => $client->getKey(),
        'client_attribute_id' => $attribute->getKey(),
    ]);

    ClientAttributeValue::factory()->for($client)->of($attribute, $file->toValue()->toArray())->create();

    return [$client, $attribute, $file];
}

describe('sending with an attachment', function (): void {
    it('attaches the file the client answered with', function (): void {
        [$client, $attribute, $file] = clientWithContract();

        $delivery = sendNow(scheduleAttaching($client, [TemplateBinding::attribute($attribute->getKey())]));

        expect($delivery->status)->toBe(NotificationDeliveryStatus::Sent);

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) use ($file): bool {
            $attachments = $mail->attachments();

            return count($attachments) === 1
                && $attachments[0]->as === $file->original_name;
        });
    });

    it('attaches one file per repeating row', function (): void {
        $certificates = ClientAttribute::factory()->repeaterOfFiles()->create(['name' => 'Certificates']);
        $client = Client::factory()->create();

        $rows = collect(['iso.pdf', 'policy.pdf'])->map(function (string $name) use ($client, $certificates): array {
            $file = ClientAttributeFile::factory()->stored()->create([
                'client_id' => $client->getKey(),
                'client_attribute_id' => $certificates->getKey(),
                'original_name' => $name,
            ]);

            return ['label' => $name, 'document' => $file->toValue()->toArray()];
        })->all();

        ClientAttributeValue::factory()->for($client)->of($certificates, $rows)->create();

        $delivery = sendNow(scheduleAttaching($client, [
            TemplateBinding::attribute($certificates->getKey(), ['document']),
        ]));

        expect($delivery->status)->toBe(NotificationDeliveryStatus::Sent);

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail): bool {
            return collect($mail->attachments())->pluck('as')->all() === ['iso.pdf', 'policy.pdf'];
        });
    });

    it('records what went out on the delivery', function (): void {
        [$client, $attribute, $file] = clientWithContract();

        $delivery = sendNow(scheduleAttaching($client, [TemplateBinding::attribute($attribute->getKey())]));

        expect($delivery->attachments)->toBe([['name' => $file->original_name, 'size' => $file->size]]);
    });

    it('sends the file the client holds now, not the one it held then', function (): void {
        [$client, $attribute, $old] = clientWithContract();

        $new = ClientAttributeFile::factory()->stored()->create([
            'client_id' => $client->getKey(),
            'client_attribute_id' => $attribute->getKey(),
            'original_name' => 'contract-v2.pdf',
        ]);

        $client->attributeValues()->firstOrFail()->update(['value' => $new->toValue()->toArray()]);

        sendNow(scheduleAttaching($client, [TemplateBinding::attribute($attribute->getKey())]));

        Mail::assertSent(NotificationMail::class, fn (NotificationMail $mail): bool => collect($mail->attachments())
            ->pluck('as')->all() === ['contract-v2.pdf']);
    });
});

describe('an attachment that cannot be found', function (): void {
    it('fails the delivery rather than sending an email without it', function (): void {
        $attribute = ClientAttribute::factory()->ofType(ClientAttributeType::File)->create(['name' => 'Signed contract']);
        $client = Client::factory()->create();

        $delivery = sendNow(scheduleAttaching($client, [TemplateBinding::attribute($attribute->getKey())]));

        expect($delivery->status)->toBe(NotificationDeliveryStatus::Failed)
            ->and($delivery->failure_message)->toContain('Signed contract');

        Mail::assertNothingSent();
    });

    it('fails when the attribute has been retired', function (): void {
        [$client, $attribute] = clientWithContract();

        $attribute->update(['is_active' => false]);

        expect(sendNow(scheduleAttaching($client, [TemplateBinding::attribute($attribute->getKey())]))->status)
            ->toBe(NotificationDeliveryStatus::Failed);
    });

    /*
    | An answer is ordinary JSON on a record anybody who can edit the client can edit, so
    | the file row it names is checked against the client being sent to.
    */
    it('will not attach a file belonging to another client', function (): void {
        [, $attribute, $theirs] = clientWithContract();

        $client = Client::factory()->create();
        ClientAttributeValue::factory()->for($client)->of($attribute, $theirs->toValue()->toArray())->create();

        expect(sendNow(scheduleAttaching($client, [TemplateBinding::attribute($attribute->getKey())]))->status)
            ->toBe(NotificationDeliveryStatus::Failed);
    });
});

describe('choosing attachments on a form', function (): void {
    it('saves what was ticked on the schedule', function (): void {
        [$client, $attribute] = clientWithContract();

        actingAs(administrator())
            ->post(route('clients.schedules.store', $client), [
                'template' => EmailTemplate::GeneralReminder->value,
                'frequency' => NotificationFrequency::Yearly->value,
                'starts_at' => CarbonImmutable::now()->addWeek()->format('Y-m-d\TH:i'),
                'is_enabled' => '1',
                'attachments' => [TemplateBinding::attribute($attribute->getKey())->token()],
            ])
            ->assertSessionHasNoErrors();

        $schedule = NotificationSchedule::query()->latest('id')->firstOrFail();

        expect($schedule->attachment_bindings->tokens())
            ->toBe([TemplateBinding::attribute($attribute->getKey())->token()]);
    });

    it('refuses an attachment on a schedule that has no client to read files from', function (): void {
        [, $attribute] = clientWithContract();

        actingAs(administrator())
            ->post(route('cadence.schedules.store'), [
                'target' => NotificationTarget::Recipients->value,
                'name' => 'Internal digest',
                'recipients' => "ops@northwind.test\n",
                'template' => EmailTemplate::StatusUpdate->value,
                'frequency' => NotificationFrequency::Monthly->value,
                'starts_at' => CarbonImmutable::now()->addWeek()->format('Y-m-d\TH:i'),
                'is_enabled' => '1',
                'attachments' => [TemplateBinding::attribute($attribute->getKey())->token()],
            ])
            ->assertSessionHasErrors('attachments.0');
    });
});
