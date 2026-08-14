<?php

declare(strict_types=1);

namespace App\Data\Audits;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Model;

final readonly class RecordAuditData
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public AuditAction $action,
        public ?Model $auditable = null,
        public ?array $oldValues = null,
        public ?array $newValues = null,
        public ?array $metadata = null,
    ) {}
}
