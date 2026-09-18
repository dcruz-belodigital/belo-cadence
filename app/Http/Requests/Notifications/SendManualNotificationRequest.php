<?php

declare(strict_types=1);

namespace App\Http\Requests\Notifications;

use App\Data\Notifications\NotificationDispatch;
use App\Enums\ClientStatus;
use App\Enums\EmailTemplate;
use App\Enums\NotificationTarget;
use App\Http\Requests\Concerns\BindsTemplateSlots;
use App\Http\Requests\Concerns\ParsesRecipientLists;
use App\Models\Client;
use App\Rules\EmailAddressRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One send, right now, to a client or to a list of addresses typed on the spot.
 */
final class SendManualNotificationRequest extends FormRequest
{
    use BindsTemplateSlots;
    use ParsesRecipientLists;

    public const MAX_RECIPIENTS = 50;

    /**
     * The permission is checked here so an unauthorised send is refused before anything
     * is looked up. Whether a particular client may be emailed is the `notify` ability,
     * which the controller asks once the client is known.
     */
    public function authorize(): bool
    {
        return $this->user()->can('notifyAny', Client::class);
    }

    public function target(): NotificationTarget
    {
        return NotificationTarget::tryFrom($this->string('target')->toString())
            ?? NotificationTarget::Client;
    }

    /**
     * Only the fields the chosen target submits are ruled on, so no failure can land on
     * a field the form never showed.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isClient = $this->target() === NotificationTarget::Client;

        $rules = [
            'target' => ['required', Rule::enum(NotificationTarget::class)],

            // A template may only be used by the kind of send it was written for.
            'template' => ['required', Rule::in(array_map(
                static fn (EmailTemplate $template): string => $template->value,
                EmailTemplate::for($this->target()),
            ))],

            ...$this->templateSlotRules(),
        ];

        if ($isClient) {
            // Archived and inactive clients are not emailable, so they are not selectable.
            $rules['client'] = [
                'required',
                'integer',
                Rule::exists('clients', 'id')
                    ->whereNull('deleted_at')
                    ->where('status', ClientStatus::Active->value),
            ];
        } else {
            $rules['name'] = ['required', 'string', 'max:255'];
            $rules['recipients'] = ['required', 'array', 'max:'.self::MAX_RECIPIENTS];
            $rules['recipients.*'] = ['string', new EmailAddressRule];
        }

        if ($this->string('template')->toString() === EmailTemplate::Blank->value) {
            $rules['subject'] = ['required', 'string', 'max:255'];
            $rules['message'] = ['required', 'string', 'max:5000'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'target' => __('cadence.fields.target'),
            'client' => __('deliveries.fields.client'),
            'template' => __('deliveries.fields.template'),
            'name' => __('cadence.fields.name'),
            'recipients' => __('cadence.fields.recipients'),
            'recipients.*' => __('cadence.fields.recipients'),
            'subject' => __('cadence.fields.subject'),
            'message' => __('cadence.fields.message'),
            ...$this->templateSlotNames(),
        ];
    }

    /**
     * The client this send is for, already validated as one that may be emailed, or null
     * when the send is addressed to a typed list.
     */
    public function client(): ?Client
    {
        return $this->target() === NotificationTarget::Client
            ? Client::query()->findOrFail($this->integer('client'))
            : null;
    }

    public function toDispatch(): NotificationDispatch
    {
        $template = EmailTemplate::from($this->string('template')->toString());
        $client = $this->client();

        return new NotificationDispatch(
            template: $template,
            clientId: $client?->getKey(),
            targetName: $client instanceof Client ? $client->name : $this->string('name')->trim()->value(),
            recipients: $client instanceof Client ? [$client->email] : $this->recipientAddresses(),
            recipientName: $client?->name,
            subject: $template->hasOwnCopy() ? null : $this->string('subject')->trim()->value(),
            message: $template->hasOwnCopy() ? null : $this->string('message')->trim()->value(),
            templateBindings: $this->templateBindings(),
        );
    }

    /**
     * A send to a typed list has no client, so its slots offer only a literal.
     */
    protected function boundClientId(): ?int
    {
        return $this->target() === NotificationTarget::Client && $this->filled('client')
            ? $this->integer('client')
            : null;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeSplitRecipients();
    }
}
