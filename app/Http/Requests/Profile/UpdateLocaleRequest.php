<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use App\Enums\Locale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateLocaleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', Rule::enum(Locale::class)],
        ];
    }

    public function locale(): Locale
    {
        return Locale::from($this->string('locale')->toString());
    }
}
