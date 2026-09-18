<?php

declare(strict_types=1);

namespace App\Data\Notifications;

use App\Enums\ClientAttributeType;
use App\Enums\EmailTemplate;
use App\Models\ClientAttribute;
use App\ValueObjects\TemplateBinding;

/**
 * A blank in an email template that is filled in per schedule.
 *
 * When a schedule chooses this template, each slot is bound either to one of the
 * client's attributes — possibly a field inside a repeater, possibly a whole repeater —
 * or to a literal typed for that schedule alone.
 *
 * `accepts` lists the attribute types that can fill it; an empty list accepts any.
 * `isMultiple` says whether it can take a path that runs through a repeater and so
 * yields a list of values rather than one. `isRequired` says the form may not leave it
 * unbound — which is a stronger thing than "the answer must exist", and most slots do not
 * need it.
 */
final readonly class EmailTemplateSlot
{
    /**
     * @param  list<ClientAttributeType>  $accepts
     */
    public function __construct(
        public string $key,
        public array $accepts = [],
        public bool $isMultiple = false,
        public bool $isRequired = false,
        public ?string $label = null,
    ) {}

    public function acceptsType(ClientAttributeType $type): bool
    {
        return $this->accepts === [] ? $type->isBasic() : in_array($type, $this->accepts, true);
    }

    /**
     * What this slot may be bound to, as the form submits it: token => label.
     *
     * One flat list rather than a tree, so a slot is one ordinary select. A path that
     * runs through a repeater is offered only to a slot that can hold a list, which is
     * the single rule that makes "a field inside a repeater" and "a repeater inside a
     * repeater" the same mechanism.
     *
     * @param  iterable<ClientAttribute>  $attributes
     * @return array<string, string>
     */
    public function choicesFrom(iterable $attributes): array
    {
        $choices = [];

        foreach ($attributes as $attribute) {
            foreach ($attribute->bindablePaths() as $bindable) {
                if (! $this->acceptsType($bindable['type'])) {
                    continue;
                }

                if ($bindable['repeated'] && ! $this->isMultiple) {
                    continue;
                }

                $choices[TemplateBinding::attribute($attribute->getKey(), $bindable['path'])->token()] = $bindable['label'];
            }
        }

        return $choices;
    }

    /**
     * A placeholder typed into the blank template's message names itself.
     */
    public function label(EmailTemplate $template): string
    {
        return $this->label ?? (string) __('mail.notifications.'.$template->value.'.slots.'.$this->key);
    }
}
