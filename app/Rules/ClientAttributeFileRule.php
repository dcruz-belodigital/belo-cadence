<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\ClientAttributeFile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Validates one answer to a file attribute, wherever in an answer it sits.
 *
 * By the time a rule sees it, the form has already reduced the two things a file cell can
 * submit to one value: a fresh upload, or the id of a file this client already holds. The
 * id was resolved against that client's own rows, so there is nothing left to check about
 * it — only an upload has anything to prove.
 *
 * The failures are Laravel's own message keys rather than new ones, so they read exactly
 * like every other upload failure and are already translated into both catalogues.
 */
final class ClientAttributeFileRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // A file already uploaded, referred to by id. It was checked when it arrived.
        if (is_int($value)) {
            return;
        }

        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('validation.uploaded')->translate();

            return;
        }

        if ((int) $value->getSize() > ClientAttributeFile::MAX_KILOBYTES * 1024) {
            $fail('validation.max.file')->translate(['max' => ClientAttributeFile::MAX_KILOBYTES]);

            return;
        }

        /*
        | The extension is guessed from the contents rather than read off the name, which
        | is what Laravel's own `mimes` rule does — a `.pdf` that is really a script is
        | refused for what it is, not for what it was called.
        */
        if (! in_array((string) $value->guessExtension(), ClientAttributeFile::EXTENSIONS, true)) {
            $fail('validation.mimes')->translate(['values' => implode(', ', ClientAttributeFile::EXTENSIONS)]);
        }
    }
}
