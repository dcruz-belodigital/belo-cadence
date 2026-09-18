<?php

declare(strict_types=1);

namespace App\Http\Requests\Notifications;

use App\Http\Requests\Concerns\ConfiguresNotificationSchedules;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateNotificationScheduleRequest extends FormRequest
{
    use ConfiguresNotificationSchedules;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // An existing schedule may keep an anchor in the past: that is where its
        // recurrence started.
        return $this->scheduleRules(mustStartInFuture: false);
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
