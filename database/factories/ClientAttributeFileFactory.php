<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClientAttributeType;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\ClientAttributeFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClientAttributeFile>
 */
final class ClientAttributeFileFactory extends Factory
{
    protected $model = ClientAttributeFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'client_attribute_id' => ClientAttribute::factory()->ofType(ClientAttributeType::File),
            'uploaded_by_user_id' => null,
            'disk' => ClientAttributeFile::DISK,
            'path' => ClientAttributeFile::DIRECTORY.'/'.Str::random(40).'.pdf',
            'original_name' => 'contract.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ];
    }

    /**
     * A file whose bytes are really on the disk, for anything that reads or deletes them.
     */
    public function stored(string $contents = 'Cadence test file'): static
    {
        return $this->afterCreating(function (ClientAttributeFile $file) use ($contents): void {
            Storage::disk($file->disk)->put($file->path, $contents);
        });
    }
}
