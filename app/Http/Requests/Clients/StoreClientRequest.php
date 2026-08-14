<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\Data\ClientNotifications\ClientNotificationScheduleData;
use App\Data\Clients\CreateClientData;
use App\Enums\ClientStatus;
use App\Http\Requests\Concerns\ConvertsViewerDateTimes;
use App\Models\DefaultClientNotification;
use App\Rules\EmailAddressRule;
use App\ValueObjects\EmailAddress;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreClientRequest extends FormRequest
{
    use ConvertsViewerDateTimes;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', new EmailAddressRule, Rule::unique('clients', 'email')],
            'status' => ['required', Rule::enum(ClientStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],

            'schedules' => ['array'],
            'schedules.*.default_id' => ['required', 'integer', Rule::exists('default_client_notifications', 'id')],
            'schedules.*.starts_at' => ['required', 'date', 'after:now'],
            'schedules.*.is_enabled' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'schedules.*.starts_at' => __('cadence.fields.starts_at'),
        ];
    }

    public function toData(): CreateClientData
    {
        return new CreateClientData(
            name: $this->string('name')->toString(),
            email: new EmailAddress($this->string('email')->toString()),
            status: ClientStatus::from($this->string('status')->toString()),
            notes: $this->filled('notes') ? $this->string('notes')->toString() : null,
            notificationSchedules: $this->notificationSchedules(),
        );
    }

    /**
     * Only the rows the user ticked are validated, and their original position is kept
     * so any error is reported against the row it belongs to.
     */
    protected function prepareForValidation(): void
    {
        /** @var array<int, array<string, mixed>> $schedules */
        $schedules = is_array($this->input('schedules')) ? $this->input('schedules') : [];

        $applied = [];

        foreach ($schedules as $index => $schedule) {
            if (! is_array($schedule) || (bool) ($schedule['apply'] ?? false) === false) {
                continue;
            }

            $schedule['starts_at'] = $this->toUtcDateTime($schedule['starts_at'] ?? null);

            $applied[$index] = $schedule;
        }

        $this->merge(['schedules' => $applied]);
    }

    /**
     * The template and frequency of an applied schedule always come from the stored
     * default, never from the submitted form.
     *
     * @return list<ClientNotificationScheduleData>
     */
    private function notificationSchedules(): array
    {
        /** @var array<int, array<string, mixed>> $schedules */
        $schedules = $this->validated('schedules') ?? [];

        if ($schedules === []) {
            return [];
        }

        $defaults = DefaultClientNotification::query()
            ->findMany(array_column($schedules, 'default_id'))
            ->keyBy('id');

        $data = [];

        foreach ($schedules as $schedule) {
            $default = $defaults->get((int) $schedule['default_id']);

            if (! $default instanceof DefaultClientNotification) {
                continue;
            }

            $data[] = new ClientNotificationScheduleData(
                template: $default->template,
                frequency: $default->frequency,
                startsAt: CarbonImmutable::parse((string) $schedule['starts_at'])->setTimezone('UTC'),
                isEnabled: (bool) ($schedule['is_enabled'] ?? false),
            );
        }

        return $data;
    }
}
