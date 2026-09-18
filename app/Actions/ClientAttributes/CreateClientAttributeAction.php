<?php

declare(strict_types=1);

namespace App\Actions\ClientAttributes;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Data\ClientAttributes\ClientAttributeData;
use App\Enums\AuditAction;
use App\Models\ClientAttribute;
use Illuminate\Support\Facades\DB;

/**
 * Defines a new field for clients.
 *
 * An attribute starts life with no answers against it, so nothing else has to happen:
 * clients that already exist simply have nothing recorded until somebody edits them.
 */
final class CreateClientAttributeAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(ClientAttributeData $data): ClientAttribute
    {
        return DB::transaction(function () use ($data): ClientAttribute {
            $attribute = ClientAttribute::query()->create([
                'key' => $data->key,
                'name' => $data->name,
                'type' => $data->type,
                'hint' => $data->hint,
                'options' => $data->options,
                'fields' => $data->fields,
                'is_required' => $data->isRequired,
                'is_active' => $data->isActive,
                'position' => $data->position,
            ]);

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::ClientAttributeCreated,
                auditable: $attribute,
                newValues: $attribute->auditShape(),
            ));

            return $attribute;
        });
    }
}
