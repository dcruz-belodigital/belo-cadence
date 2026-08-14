<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use App\Enums\ColorScheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateColorSchemeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'color_scheme' => ['required', Rule::enum(ColorScheme::class)],
        ];
    }

    public function colorScheme(): ColorScheme
    {
        return ColorScheme::from($this->string('color_scheme')->toString());
    }
}
