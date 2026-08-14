<?php

declare(strict_types=1);

namespace App\Support\Csv;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turns a described CSV document into a download.
 *
 * A byte order mark is written first so spreadsheet applications open accented
 * characters correctly. The escape character is passed explicitly and left empty so
 * fields are quoted the standard way, which is also what our own reader expects.
 */
final class CsvWriter
{
    private const ESCAPE = '';

    public function download(CsvDocument $document): StreamedResponse
    {
        return response()->streamDownload(function () use ($document): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $document->headers, escape: self::ESCAPE);

            foreach ($document->rows as $row) {
                fputcsv($handle, $row, escape: self::ESCAPE);
            }

            fclose($handle);
        }, $document->filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
