<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Data\Notifications\EmailTemplateSlot;
use App\Enums\EmailTemplate;
use App\Models\ClientAttribute;
use App\Support\TemplateSlots;
use App\ValueObjects\TemplateAttachments;
use App\ValueObjects\TemplateBinding;
use App\ValueObjects\TemplateBindings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;

/**
 * Filling in a template's blanks, shared by the schedule forms and the send-by-hand form.
 *
 * What a slot may be bound to depends on the template chosen, on the attributes that
 * exist, and on whether this notification is about a client at all — a recipient list has
 * no client whose answers could be read, so its slots offer only a literal.
 *
 * Attachments are the same question asked of files instead of wording, and are handled
 * here for the same reason: both forms ask it, and neither may offer a client's files to
 * a notification that is not about that client.
 */
trait BindsTemplateSlots
{
    /**
     * @var Collection<int, ClientAttribute>|null
     */
    private ?Collection $bindableAttributes = null;

    /**
     * Whether a client's answers are reachable from here.
     */
    abstract protected function boundClientId(): ?int;

    /**
     * The blanks the chosen template leaves.
     *
     * @return list<EmailTemplateSlot>
     */
    public function templateSlots(): array
    {
        $template = EmailTemplate::tryFrom($this->string('template')->toString());

        if (! $template instanceof EmailTemplate) {
            return [];
        }

        return TemplateSlots::for($template, $this->string('message')->toString());
    }

    /**
     * One rule per slot, so a message lands on the slot it belongs to.
     *
     * @return array<string, mixed>
     */
    public function templateSlotRules(): array
    {
        $rules = ['template_bindings' => ['array']];

        foreach ($this->templateSlots() as $slot) {
            $key = 'template_bindings.'.$slot->key;
            $tokens = [TemplateBinding::MANUAL, ...array_keys($this->choicesFor($slot))];

            $rules[$key.'.source'] = [
                $slot->isRequired ? 'required' : 'nullable',
                'string',
                Rule::in($tokens),
            ];

            $rules[$key.'.value'] = [
                // A literal is only expected when a literal was chosen.
                Rule::requiredIf(fn (): bool => $this->input($key.'.source') === TemplateBinding::MANUAL),
                'nullable',
                'string',
                'max:255',
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function templateSlotNames(): array
    {
        $template = EmailTemplate::tryFrom($this->string('template')->toString());
        $names = [];

        foreach ($this->templateSlots() as $slot) {
            $label = $template instanceof EmailTemplate ? $slot->label($template) : $slot->key;

            $names['template_bindings.'.$slot->key.'.source'] = $label;
            $names['template_bindings.'.$slot->key.'.value'] = $label;
        }

        return $names;
    }

    /**
     * The rules for the files this notification attaches.
     *
     * Only the tokens this very form offered are accepted, and a send with no client
     * offers none — so a tampered payload cannot attach a file the form never showed.
     *
     * @return array<string, mixed>
     */
    public function attachmentRules(): array
    {
        return [
            'attachments' => ['array', 'max:'.TemplateAttachments::MAX],
            'attachments.*' => ['string', Rule::in(array_keys($this->attachmentChoices()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attachmentNames(): array
    {
        return [
            'attachments' => (string) __('cadence.attachments.title'),
            'attachments.*' => (string) __('cadence.attachments.title'),
        ];
    }

    /**
     * Every file this notification could attach: token => label.
     *
     * @return array<string, string>
     */
    public function attachmentChoices(): array
    {
        if ($this->boundClientId() === null) {
            return [];
        }

        return TemplateSlots::attachmentSlot()->choicesFrom($this->bindableAttributes());
    }

    public function templateAttachments(): TemplateAttachments
    {
        $submitted = $this->validated('attachments');

        return TemplateAttachments::fromTokens(is_array($submitted) ? $submitted : []);
    }

    public function templateBindings(): TemplateBindings
    {
        $submitted = $this->validated('template_bindings');
        $bindings = [];

        foreach ($this->templateSlots() as $slot) {
            $source = is_array($submitted) ? ($submitted[$slot->key]['source'] ?? null) : null;

            if (! is_string($source) || $source === '') {
                continue;
            }

            $binding = TemplateBinding::fromToken($source, is_array($submitted) ? ($submitted[$slot->key]['value'] ?? null) : null);

            if ($binding !== null) {
                $bindings[$slot->key] = $binding;
            }
        }

        return new TemplateBindings($bindings);
    }

    /**
     * What this slot offers, beyond a literal.
     *
     * @return array<string, string>
     */
    public function choicesFor(EmailTemplateSlot $slot): array
    {
        // Without a client there is nobody whose answers could fill the blank.
        if ($this->boundClientId() === null) {
            return [];
        }

        return $slot->choicesFrom($this->bindableAttributes());
    }

    /**
     * @return Collection<int, ClientAttribute>
     */
    private function bindableAttributes(): Collection
    {
        return $this->bindableAttributes ??= ClientAttribute::query()->active()->get();
    }
}
