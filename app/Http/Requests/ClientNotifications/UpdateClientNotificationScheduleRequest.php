<?php

declare(strict_types=1);

namespace App\Http\Requests\ClientNotifications;

use App\Data\ClientNotifications\ClientNotificationScheduleData;
use App\Enums\ClientEmailTemplate;
use App\Enums\ClientNotificationFrequency;
use App\Http\Requests\Concerns\ConvertsViewerDateTimes;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateClientNotificationScheduleRequest extends FormRequest
{
    use ConvertsViewerDateTimes;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template' => ['required', Rule::enum(ClientEmailTemplate::class)],
            'frequency' => ['required', Rule::enum(ClientNotificationFrequency::class)],
            // An existing schedule may keep an anchor in the past: that is where its
            // recurrence started.
            'starts_at' => ['required', 'date'],
            'is_enabled' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'starts_at' => __('cadence.fields.starts_at'),
            'is_enabled' => __('cadence.fields.is_enabled'),
        ];
    }

    public function toData(): ClientNotificationScheduleData
    {
        return new ClientNotificationScheduleData(
            template: ClientEmailTemplate::from($this->string('template')->toString()),
            frequency: ClientNotificationFrequency::from($this->string('frequency')->toString()),
            startsAt: CarbonImmutable::parse($this->string('starts_at')->toString())->setTimezone('UTC'),
            isEnabled: $this->boolean('is_enabled'),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['starts_at' => $this->toUtcDateTime($this->input('starts_at'))]);
    }
}
