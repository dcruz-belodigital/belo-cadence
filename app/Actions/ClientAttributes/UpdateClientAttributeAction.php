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
 * Changes what an attribute is called, how it is explained and what it offers.
 *
 * `key` and `type` are deliberately not written. The key names a CSV column that other
 * people's files and pipelines already use, and the type is the shape every answer
 * already recorded is stored in — changing it would invalidate them all with no sensible
 * way to convert. Retiring the attribute and creating another is the way to change type.
 */
final class UpdateClientAttributeAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(ClientAttribute $attribute, ClientAttributeData $data): ClientAttribute
    {
        return DB::transaction(function () use ($attribute, $data): ClientAttribute {
            $before = $attribute->auditShape();

            $attribute->update([
                'name' => $data->name,
                'hint' => $data->hint,
                'options' => $data->options,
                'fields' => $data->fields,
                'is_required' => $data->isRequired,
                'is_active' => $data->isActive,
                'position' => $data->position,
            ]);

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::ClientAttributeUpdated,
                auditable: $attribute,
                oldValues: $before,
                newValues: $attribute->auditShape(),
            ));

            return $attribute;
        });
    }
}
