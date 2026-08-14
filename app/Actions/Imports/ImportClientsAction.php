<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Actions\Audits\RecordAuditAction;
use App\Actions\Clients\CreateClientAction;
use App\Actions\Clients\UpdateClientAction;
use App\Data\Audits\RecordAuditData;
use App\Data\Clients\CreateClientData;
use App\Data\Clients\UpdateClientData;
use App\Data\Imports\ColumnMapping;
use App\Data\Imports\ImportResult;
use App\Enums\AuditAction;
use App\Enums\ClientStatus;
use App\Models\Client;
use App\Rules\EmailAddressRule;
use App\Support\Csv\CsvData;
use App\Support\Csv\CsvReader;
use App\ValueObjects\EmailAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Imports clients from a CSV file.
 *
 * The column mapping says which heading in the file provides each column, so a file does
 * not have to use this application's own column names.
 *
 * The whole file is validated with the same rules the forms use before anything is
 * written, and every row goes through the ordinary create and update actions, so
 * imported clients are indistinguishable from clients entered by hand — including
 * their audit trail. Nothing is persisted unless every row is acceptable.
 */
final class ImportClientsAction
{
    public function __construct(
        private readonly CsvReader $csvReader,
        private readonly CreateClientAction $createClient,
        private readonly UpdateClientAction $updateClient,
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(string $csvPath, ColumnMapping $mapping): ImportResult
    {
        $csv = $this->csvReader->read($csvPath);

        $rows = $this->validatedRows($csv, $mapping);

        $result = DB::transaction(function () use ($rows): ImportResult {
            $created = 0;
            $updated = 0;

            foreach ($rows as $row) {
                $existing = $row['id'] === null
                    ? null
                    : Client::query()->find($row['id']);

                if ($existing instanceof Client) {
                    ($this->updateClient)($existing, new UpdateClientData(
                        name: $row['name'],
                        email: $row['email'],
                        status: $row['status'],
                        notes: $row['notes'],
                    ));

                    $updated++;

                    continue;
                }

                ($this->createClient)(new CreateClientData(
                    name: $row['name'],
                    email: $row['email'],
                    status: $row['status'],
                    notes: $row['notes'],
                ));

                $created++;
            }

            return new ImportResult($created, $updated);
        });

        ($this->recordAudit)(new RecordAuditData(
            action: AuditAction::ClientsImported,
            metadata: [
                'created' => $result->created,
                'updated' => $result->updated,
            ],
        ));

        return $result;
    }

    /**
     * @return list<array{id: int|null, name: string, email: EmailAddress, status: ClientStatus, notes: string|null}>
     */
    private function validatedRows(CsvData $csv, ColumnMapping $mapping): array
    {
        if ($csv->rows === []) {
            throw ValidationException::withMessages(['file' => __('imports.errors.no_rows')]);
        }

        $errors = [];
        $rows = [];
        $seenEmails = [];

        foreach ($csv->rows as $index => $row) {
            // Spreadsheet line numbers include the header row.
            $line = $index + 2;

            $values = [
                'id' => $mapping->value($row, 'id'),
                'name' => $mapping->value($row, 'name'),
                'email' => $mapping->value($row, 'email'),
                'status' => $mapping->value($row, 'status'),
                'notes' => $mapping->value($row, 'notes'),
            ];

            $validator = Validator::make(array_map(
                static fn (string $value): ?string => $value === '' ? null : $value,
                $values,
            ), [
                'id' => ['nullable', 'integer', Rule::exists('clients', 'id')],
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'max:255',
                    new EmailAddressRule,
                    Rule::unique('clients', 'email')->ignore($values['id'] === '' ? null : $values['id']),
                ],
                'status' => ['required', Rule::enum(ClientStatus::class)],
                'notes' => ['nullable', 'string', 'max:5000'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = __('imports.errors.row', ['line' => $line, 'message' => $message]);
                }

                continue;
            }

            $email = new EmailAddress($values['email']);

            if (isset($seenEmails[$email->value])) {
                $errors[] = __('imports.errors.row', [
                    'line' => $line,
                    'message' => __('imports.errors.duplicate_in_file', [
                        'value' => $email->value,
                        'line' => $seenEmails[$email->value],
                    ]),
                ]);

                continue;
            }

            $seenEmails[$email->value] = $line;

            $rows[] = [
                'id' => $values['id'] === '' ? null : (int) $values['id'],
                'name' => $values['name'],
                'email' => $email,
                'status' => ClientStatus::from($values['status']),
                'notes' => $values['notes'] === '' ? null : $values['notes'],
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        return $rows;
    }
}
