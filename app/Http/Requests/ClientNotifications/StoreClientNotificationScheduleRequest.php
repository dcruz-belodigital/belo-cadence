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

final class StoreClientNotificationScheduleRequest extends FormRequest
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
            // A new schedule always points at a future occurrence: the application never
            // invents a send date, and never sends for a date somebody has already missed.
            'starts_at' => ['required', 'date', 'after:now'],
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
