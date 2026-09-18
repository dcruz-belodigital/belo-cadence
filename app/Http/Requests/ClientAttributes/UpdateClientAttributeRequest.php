<?php

declare(strict_types=1);

namespace App\Http\Requests\ClientAttributes;

use App\Data\ClientAttributes\ClientAttributeData;
use App\Http\Requests\Concerns\DefinesClientAttributes;
use App\Models\ClientAttribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * There is deliberately no rule for `type`, and none for the identifier: neither is
 * offered by the edit form, and a field that cannot be reached has no rule, because a
 * message with nowhere to appear is a failure nobody can see.
 */
final class UpdateClientAttributeRequest extends FormRequest
{
    use DefinesClientAttributes;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('client_attributes', 'name')->ignore($this->definition()->getKey()),
            ],
            ...$this->definitionRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('client_attributes.fields.name'),
            ...$this->definitionAttributes(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['options.required' => __('client_attributes.errors.select_needs_option')];
    }

    public function toData(): ClientAttributeData
    {
        return $this->toDefinitionData($this->definition()->type);
    }

    protected function prepareForValidation(): void
    {
        $this->prepareDefinitionInput();
    }

    protected function definition(): ClientAttribute
    {
        $attribute = $this->route('attribute');

        assert($attribute instanceof ClientAttribute);

        return $attribute;
    }
}
