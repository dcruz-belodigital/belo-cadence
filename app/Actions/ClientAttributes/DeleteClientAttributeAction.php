<?php

declare(strict_types=1);

namespace App\Actions\ClientAttributes;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Enums\AuditAction;
use App\Models\ClientAttribute;
use Illuminate\Support\Facades\DB;

/**
 * Removes an attribute and every answer recorded against it.
 *
 * The answers go by foreign key rather than by hand, but how many there were is counted
 * first and kept in the audit entry: it is the only remaining trace of what was lost.
 */
final class DeleteClientAttributeAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(ClientAttribute $attribute): void
    {
        DB::transaction(function () use ($attribute): void {
            $shape = $attribute->auditShape();
            $recorded = $attribute->values()->count();

            $attribute->delete();

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::ClientAttributeDeleted,
                oldValues: $shape,
                metadata: ['deleted_values' => $recorded],
            ));
        });
    }
}
