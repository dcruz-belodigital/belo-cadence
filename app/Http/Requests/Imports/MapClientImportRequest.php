<?php

declare(strict_types=1);

namespace App\Http\Requests\Imports;

use App\Models\Client;
use App\Support\Csv\ImportTemplate;
use App\Support\Csv\ImportTemplates;

final class MapClientImportRequest extends MapImportRequest
{
    /**
     * The client shape reads the active attributes, so it is built once per request.
     */
    private ?ImportTemplate $template = null;

    public function authorize(): bool
    {
        return $this->user()->can('import', Client::class);
    }

    protected function resource(): string
    {
        return 'clients';
    }

    protected function template(): ImportTemplate
    {
        return $this->template ??= ImportTemplates::clients();
    }
}
