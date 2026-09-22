<?php

declare(strict_types=1);

namespace App\Models;

use App\ValueObjects\StoredFile;
use Carbon\CarbonImmutable;
use Database\Factories\ClientAttributeFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The bytes one client uploaded for one file attribute.
 *
 * This row is the authority on who a file belongs to. The answer that refers to it is
 * ordinary JSON in `client_attribute_values` and can be edited by anybody who can edit
 * the client, so the download route reads the owner from here instead — an answer
 * pointing at a file this client never uploaded resolves to nothing.
 *
 * Nothing deletes a row on its own. The bytes only go when the row does, and both
 * happen in `DeleteClientAttributeFilesAction`, which every path that can orphan a file
 * goes through: saving a client, and deleting an attribute. The cascade that fires when
 * an attribute is deleted would otherwise take the row and leave the bytes, which is why
 * the action deletes them before the attribute goes rather than relying on a model event
 * the database never triggers.
 *
 * @property int $id
 * @property int $client_id
 * @property int $client_attribute_id
 * @property int|null $uploaded_by_user_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Client|null $client
 * @property-read ClientAttribute|null $attribute
 * @property-read User|null $uploadedBy
 */
#[Fillable([
    'client_id',
    'client_attribute_id',
    'uploaded_by_user_id',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size',
])]
final class ClientAttributeFile extends Model
{
    /** @use HasFactory<ClientAttributeFileFactory> */
    use HasFactory;

    /**
     * The disk uploads are written to.
     *
     * The private one, deliberately: nothing a client uploads is ever reachable by URL,
     * only through the download route, which asks the client policy first.
     */
    public const DISK = 'local';

    /**
     * Where on that disk they live, so the prune command knows where to look.
     */
    public const DIRECTORY = 'client-attributes';

    /**
     * The most one upload may weigh, in kilobytes.
     */
    public const MAX_KILOBYTES = 10240;

    /**
     * The file types an upload may be.
     *
     * Documents, spreadsheets, presentations, plain data and images — the things a team
     * attaches to an email. Anything executable, and anything a browser would run if it
     * were ever served inline, is deliberately absent.
     *
     * @var list<string>
     */
    public const EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt',
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'zip',
    ];

    /**
     * The client this file belongs to, archived ones included: an archived client's page
     * still reads, so its files still download.
     *
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
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * How an answer refers to this file.
     */
    public function toValue(): StoredFile
    {
        return new StoredFile($this->getKey(), $this->original_name, $this->size);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
