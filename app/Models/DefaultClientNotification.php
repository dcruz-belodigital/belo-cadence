<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmailTemplate;
use App\Enums\NotificationFrequency;
use Carbon\CarbonImmutable;
use Database\Factories\DefaultClientNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A notification configuration that is offered when a new client is created.
 *
 * Applying it copies the values onto the client's own schedule; this record never
 * governs a schedule after the fact.
 *
 * @property int $id
 * @property EmailTemplate $template
 * @property NotificationFrequency $frequency
 * @property bool $is_enabled_by_default
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
#[Fillable(['template', 'frequency', 'is_enabled_by_default'])]
final class DefaultClientNotification extends Model
{
    /** @use HasFactory<DefaultClientNotificationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'template' => EmailTemplate::class,
            'frequency' => NotificationFrequency::class,
            'is_enabled_by_default' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
