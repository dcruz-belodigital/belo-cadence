<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Data\Settings\DefaultClientNotificationData;
use App\Data\Settings\UpdateDefaultClientNotificationsData;
use App\Enums\ClientEmailTemplate;
use App\Enums\ClientNotificationFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDefaultClientNotificationsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entries' => ['array'],
            'entries.*.template' => ['required', Rule::enum(ClientEmailTemplate::class)],
            'entries.*.frequency' => ['required', Rule::enum(ClientNotificationFrequency::class)],
            'entries.*.is_enabled_by_default' => ['boolean'],
        ];
    }

    public function toData(): UpdateDefaultClientNotificationsData
    {
        /** @var array<int, array<string, mixed>> $entries */
        $entries = $this->validated('entries') ?? [];

        $data = [];
        $seen = [];

        foreach ($entries as $entry) {
            $template = ClientEmailTemplate::from((string) $entry['template']);
            $frequency = ClientNotificationFrequency::from((string) $entry['frequency']);
            $key = $template->value.'|'.$frequency->value;

            // The same template and frequency can only be offered once.
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            $data[] = new DefaultClientNotificationData(
                template: $template,
                frequency: $frequency,
                isEnabledByDefault: (bool) ($entry['is_enabled_by_default'] ?? false),
            );
        }

        return new UpdateDefaultClientNotificationsData($data);
    }

    /**
     * Rows the user removed in the browser arrive empty, so they are dropped before
     * validation instead of being reported as errors.
     */
    protected function prepareForValidation(): void
    {
        $entries = is_array($this->input('entries')) ? $this->input('entries') : [];

        $this->merge([
            'entries' => array_values(array_filter(
                $entries,
                static fn (mixed $entry): bool => is_array($entry) && ($entry['template'] ?? '') !== '',
            )),
        ]);
    }
}
