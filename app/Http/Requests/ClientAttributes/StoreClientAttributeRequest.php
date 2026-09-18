<?php

declare(strict_types=1);

namespace App\Http\Requests\ClientAttributes;

use App\Data\ClientAttributes\ClientAttributeData;
use App\Enums\ClientAttributeType;
use App\Http\Requests\Concerns\DefinesClientAttributes;
use App\Models\ClientAttribute;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreClientAttributeRequest extends FormRequest
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
                Rule::unique('client_attributes', 'name'),
                $this->becomesAUsableColumnName(),
            ],
            'type' => ['required', Rule::enum(ClientAttributeType::class)],
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
            'type' => __('client_attributes.fields.type'),
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
        return $this->toDefinitionData(ClientAttributeType::from($this->string('type')->toString()));
    }

    protected function prepareForValidation(): void
    {
        $this->prepareDefinitionInput();
    }

    protected function definition(): ?ClientAttribute
    {
        return null;
    }

    /**
     * The identifier is derived from the name, so two names that reduce to the same one
     * would fight over a CSV column — and neither may take a column a client already has.
     */
    private function becomesAUsableColumnName(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $key = self::keyFor(is_string($value) ? $value : '');

            if ($key === '' || in_array($key, ClientAttribute::RESERVED_KEYS, true)) {
                $fail(__('client_attributes.errors.reserved_key'));

                return;
            }

            if (ClientAttribute::query()->where('key', $key)->exists()) {
                $fail(__('validation.unique', ['attribute' => __('client_attributes.fields.name')]));
            }
        };
    }
}
