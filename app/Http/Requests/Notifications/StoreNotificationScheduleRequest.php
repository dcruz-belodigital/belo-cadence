<?php

declare(strict_types=1);

namespace App\Http\Requests\Notifications;

use App\Http\Requests\Concerns\ConfiguresNotificationSchedules;
use Illuminate\Foundation\Http\FormRequest;

final class StoreNotificationScheduleRequest extends FormRequest
{
    use ConfiguresNotificationSchedules;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // A new schedule always points at a future occurrence: the application never
        // invents a send date, and never sends for a date somebody has already missed.
        return $this->scheduleRules(mustStartInFuture: true);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->scheduleAttributes();
    }

    protected function prepareForValidation(): void
    {
        $this->prepareScheduleInput();
    }
}
