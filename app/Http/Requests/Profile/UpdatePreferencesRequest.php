<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use App\Data\Users\UpdateUserPreferencesData;
use App\Enums\Locale;
use App\Enums\Theme;
use App\Enums\ColorScheme;
use App\ValueObjects\TimezoneIdentifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePreferencesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'color_scheme' => ['required', Rule::enum(ColorScheme::class)],
            /* Empty is meaningful: it means "follow the application default". */
            'theme' => ['nullable', Rule::enum(Theme::class)],
            'locale' => ['required', Rule::enum(Locale::class)],
            'timezone' => ['required', 'string', 'timezone'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'color_scheme' => __('settings.color_scheme.label'),
            'theme' => __('profile.fields.theme'),
            'locale' => __('profile.fields.locale'),
            'timezone' => __('profile.fields.timezone'),
        ];
    }

    public function toData(): UpdateUserPreferencesData
    {
        return new UpdateUserPreferencesData(
            colorScheme: ColorScheme::from($this->string('color_scheme')->toString()),
            theme: $this->filled('theme') ? Theme::from($this->string('theme')->toString()) : null,
            locale: Locale::from($this->string('locale')->toString()),
            timezone: new TimezoneIdentifier($this->string('timezone')->toString()),
        );
    }
}
