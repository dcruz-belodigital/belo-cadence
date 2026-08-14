<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\Audits\AuditFilters;
use App\Enums\AuditAction;
use Carbon\CarbonImmutable;
use Database\Factories\AuditFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * An append-only record of who changed what.
 *
 * A null actor means the change was made by the system itself, for example by the
 * scheduler.
 *
 * @property int $id
 * @property int|null $user_id
 * @property AuditAction $action
 * @property string|null $auditable_type
 * @property int|null $auditable_id
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property array<string, mixed>|null $metadata
 * @property CarbonImmutable $created_at
 * @property-read User|null $user
 * @property-read Model|null $auditable
 */
#[Fillable([
    'user_id',
    'action',
    'auditable_type',
    'auditable_id',
    'old_values',
    'new_values',
    'metadata',
])]
final class Audit extends Model
{
    /** @use HasFactory<AuditFactory> */
    use HasFactory;

    /**
     * Audit entries are never updated.
     */
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    /**
     * Applies the audit table's search, filters and sorting.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function filtered(Builder $query, AuditFilters $filters): void
    {
        $query
            ->when($filters->search, fn (Builder $audits, string $search): Builder => $audits
                ->whereHas('user', fn (Builder $user): Builder => $user->where(
                    fn (Builder $match): Builder => $match
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                )))
            ->when($filters->action, fn (Builder $audits, AuditAction $action): Builder => $audits
                ->where('action', $action))
            ->when($filters->auditableType, fn (Builder $audits, string $type): Builder => $audits
                ->where('auditable_type', $type))
            ->when($filters->from, fn (Builder $audits, DateTimeInterface $from): Builder => $audits
                ->where('created_at', '>=', $from))
            ->when($filters->to, fn (Builder $audits, DateTimeInterface $to): Builder => $audits
                ->where('created_at', '<=', $to))
            ->orderBy($filters->sort, $filters->direction);
    }

    /**
     * The translated name of the record type this entry refers to.
     */
    public function auditableTypeLabel(): ?string
    {
        return $this->auditable_type === null
            ? null
            : __('audit.auditable_types.'.$this->auditable_type);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
