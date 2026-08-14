<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\Models\Client;
use App\Rules\UsableCsvFileRule;
use Illuminate\Foundation\Http\FormRequest;

final class ImportClientsRequest extends FormRequest
{
    /**
     * Authorising here means an unauthorised upload is refused before the file is even
     * looked at.
     */
    public function authorize(): bool
    {
        return $this->user()->can('import', Client::class);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['bail', 'required', 'file', 'mimes:csv,txt', 'max:2048', app(UsableCsvFileRule::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => __('imports.fields.file'),
        ];
    }
}
