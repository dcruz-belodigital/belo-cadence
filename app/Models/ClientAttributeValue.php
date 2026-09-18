<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ClientAttributeValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What one client answered for one attribute.
 *
 * `value` is json rather than a typed column because a repeater's answer is a list of
 * rows, and because nothing filters or sorts by these yet. The cast round-trips a bare
 * scalar as happily as a list, so every type shares one column.
 *
 * There is no row for an unanswered attribute: clearing a value deletes it. That keeps
 * "unset" single-valued, and keeps the count of clients still using a definition honest
 * when somebody is about to delete it.
 *
 * @property int $id
 * @property int $client_id
 * @property int $client_attribute_id
 * @property mixed $value
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Client $client
 * @property-read ClientAttribute $attribute
 */
#[Fillable(['client_id', 'client_attribute_id', 'value'])]
final class ClientAttributeValue extends Model
{
    /** @use HasFactory<ClientAttributeValueFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    /**
     * @return BelongsTo<ClientAttribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ClientAttribute::class, 'client_attribute_id');
    }

    /**
     * The answer as a person reads it. Needs the `attribute` relation loaded.
     */
    public function display(): string
    {
        return $this->attribute->formatValue($this->value);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
