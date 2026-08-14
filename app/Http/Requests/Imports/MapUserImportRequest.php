<?php

declare(strict_types=1);

namespace App\Http\Requests\Imports;

use App\Models\User;
use App\Support\Csv\ImportTemplate;
use App\Support\Csv\ImportTemplates;

final class MapUserImportRequest extends MapImportRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('import', User::class);
    }

    protected function resource(): string
    {
        return 'users';
    }

    protected function template(): ImportTemplate
    {
        return ImportTemplates::users();
    }
}
