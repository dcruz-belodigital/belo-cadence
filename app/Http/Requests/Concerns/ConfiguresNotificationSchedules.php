<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Data\Notifications\NotificationScheduleData;
use App\Enums\ClientStatus;
use App\Enums\EmailTemplate;
use App\Enums\NotificationFrequency;
use App\Enums\NotificationTarget;
use App\Models\Client;
use App\Models\NotificationSchedule;
use App\Rules\EmailAddressRule;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;

/**
 * The half of a schedule form that both creating and editing share: which target it
 * has, and therefore which fields and which templates are even allowed.
 *
 * A schedule reached through a client is a client schedule and cannot be anything else,
 * which is why the target is read from the route before it is read from the form.
 */
trait ConfiguresNotificationSchedules
{
    use BindsTemplateSlots;
    use ConvertsViewerDateTimes;
    use ParsesRecipientLists;

    public const MAX_RECIPIENTS = 50;

    /**
     * Whether this form is configuring a client schedule or a recipient-list one.
     *
     * A schedule never changes what it targets, so an edit reads the target off the
     * schedule and a create through a client reads it off the route. Only the general
     * create form actually takes the answer from the input.
     */
    public function scheduleTarget(): NotificationTarget
    {
        if ($this->routeSchedule() instanceof NotificationSchedule) {
            return $this->routeSchedule()->target;
        }

        if ($this->routeClient() instanceof Client) {
            return NotificationTarget::Client;
        }

        return NotificationTarget::tryFrom($this->string('target')->toString())
            ?? NotificationTarget::Client;
    }

    public function toData(): NotificationScheduleData
    {
        $target = $this->scheduleTarget();
        $template = EmailTemplate::from($this->string('template')->toString());
        $isClient = $target === NotificationTarget::Client;

        return new NotificationScheduleData(
            template: $template,
            frequency: NotificationFrequency::from($this->string('frequency')->toString()),
            startsAt: CarbonImmutable::parse($this->string('starts_at')->toString())->setTimezone('UTC'),
            isEnabled: $this->boolean('is_enabled'),
            clientId: $isClient ? $this->resolvedClientId() : null,
            name: $isClient ? null : $this->string('name')->trim()->value(),
            recipients: $isClient ? [] : $this->recipientAddresses(),
            templateBindings: $this->templateBindings(),
            attachments: $this->templateAttachments(),
            subject: $template->hasOwnCopy() ? null : $this->string('subject')->trim()->value(),
            message: $template->hasOwnCopy() ? null : $this->string('message')->trim()->value(),
        );
    }

    /**
     * The rules both forms share.
     *
     * Only the fields the chosen target actually submits are ruled on. A field that
     * cannot be reached — the target on a client's own form, the recipient list on a
     * client schedule — has no rule, because a rule whose message has nowhere to appear
     * is a validation failure nobody can see.
     *
     * @param  bool  $mustStartInFuture  A new schedule may not be anchored in the past; an
     *                                   existing one keeps the anchor its recurrence began at.
     * @return array<string, mixed>
     */
    protected function scheduleRules(bool $mustStartInFuture): array
    {
        $target = $this->scheduleTarget();
        $isClient = $target === NotificationTarget::Client;

        $rules = [
            // A template may only be used by the kind of schedule it was written for.
            'template' => ['required', Rule::in(array_map(
                static fn (EmailTemplate $template): string => $template->value,
                EmailTemplate::for($target),
            ))],

            'frequency' => ['required', Rule::enum(NotificationFrequency::class)],
            'starts_at' => ['required', 'date', ...($mustStartInFuture ? ['after:now'] : [])],
            'is_enabled' => ['boolean'],

            ...$this->templateSlotRules(),
            ...$this->attachmentRules(),
        ];

        if (! $this->targetIsFixed()) {
            $rules['target'] = ['required', Rule::enum(NotificationTarget::class)];

            if ($isClient) {
                // Archived and inactive clients are not emailable, so they are not selectable.
                $rules['client'] = [
                    'required',
                    'integer',
                    Rule::exists('clients', 'id')
                        ->whereNull('deleted_at')
                        ->where('status', ClientStatus::Active->value),
                ];
            }
        }

        if (! $isClient) {
            $rules['name'] = ['required', 'string', 'max:255'];
            $rules['recipients'] = ['required', 'array', 'max:'.self::MAX_RECIPIENTS];
            $rules['recipients.*'] = ['string', new EmailAddressRule];
        }

        // The blank template is the one template whose wording is written on the schedule.
        if ($this->string('template')->toString() === EmailTemplate::Blank->value) {
            $rules['subject'] = ['required', 'string', 'max:255'];
            $rules['message'] = ['required', 'string', 'max:5000'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function scheduleAttributes(): array
    {
        return [
            'target' => __('cadence.fields.target'),
            'template' => __('cadence.fields.template'),
            'frequency' => __('cadence.fields.frequency'),
            'starts_at' => __('cadence.fields.starts_at'),
            'is_enabled' => __('cadence.fields.is_enabled'),
            'client' => __('cadence.fields.client'),
            'name' => __('cadence.fields.name'),
            'recipients' => __('cadence.fields.recipients'),
            'recipients.*' => __('cadence.fields.recipients'),
            'subject' => __('cadence.fields.subject'),
            'message' => __('cadence.fields.message'),
            ...$this->templateSlotNames(),
            ...$this->attachmentNames(),
        ];
    }

    protected function prepareScheduleInput(): void
    {
        $this->merge(['starts_at' => $this->toUtcDateTime($this->input('starts_at'))]);

        $this->mergeSplitRecipients();
    }

    /**
     * Whether the target was decided before the form was even shown.
     */
    private function targetIsFixed(): bool
    {
        return $this->routeSchedule() instanceof NotificationSchedule
            || $this->routeClient() instanceof Client;
    }

    /**
     * A list schedule has no client, so its slots offer only a literal.
     */
    protected function boundClientId(): ?int
    {
        return $this->scheduleTarget() === NotificationTarget::Client ? $this->resolvedClientId() : null;
    }

    private function resolvedClientId(): ?int
    {
        $schedule = $this->routeSchedule();

        if ($schedule instanceof NotificationSchedule) {
            return $schedule->client_id;
        }

        $routeClient = $this->routeClient();

        if ($routeClient instanceof Client) {
            return $routeClient->getKey();
        }

        return $this->filled('client') ? $this->integer('client') : null;
    }

    private function routeClient(): ?Client
    {
        $client = $this->route('client');

        return $client instanceof Client ? $client : null;
    }

    private function routeSchedule(): ?NotificationSchedule
    {
        $schedule = $this->route('schedule');

        return $schedule instanceof NotificationSchedule ? $schedule : null;
    }
}
