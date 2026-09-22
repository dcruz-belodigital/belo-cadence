<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Actions\Audits\RecordAuditAction;
use App\Actions\Users\CreateUserAction;
use App\Actions\Users\UpdateUserAction;
use App\Data\Audits\RecordAuditData;
use App\Data\Imports\ColumnMapping;
use App\Data\Imports\ImportResult;
use App\Data\Users\CreateUserData;
use App\Data\Users\UpdateUserData;
use App\Enums\AuditAction;
use App\Models\User;
use App\Rules\EmailAddressRule;
use App\Support\Csv\CsvData;
use App\Support\Csv\CsvReader;
use App\Support\Csv\ImportTemplates;
use App\ValueObjects\EmailAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Imports user accounts from a CSV file.
 *
 * The column mapping says which heading in the file provides each column, so a file does
 * not have to use this application's own column names.
 *
 * Rows go through the same create and update actions the interface uses, so password
 * hashing, role assignment and audit entries behave identically. Nothing is written
 * unless the whole file is acceptable.
 */
final class ImportUsersAction
{
    public function __construct(
        private readonly CsvReader $csvReader,
        private readonly CreateUserAction $createUser,
        private readonly UpdateUserAction $updateUser,
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
                $existing = $row['id'] === null ? null : User::query()->find($row['id']);

                if ($existing instanceof User) {
                    ($this->updateUser)($existing, new UpdateUserData(
                        name: $row['name'],
                        email: $row['email'],
                        password: $row['password'],
                        roleNames: $row['roles'],
                    ));

                    $updated++;

                    continue;
                }

                ($this->createUser)(new CreateUserData(
                    name: $row['name'],
                    email: $row['email'],
                    password: (string) $row['password'],
                    isActive: $row['is_active'],
                    roleNames: $row['roles'],
                ));

                $created++;
            }

            return new ImportResult($created, $updated);
        });

        ($this->recordAudit)(new RecordAuditData(
            action: AuditAction::UsersImported,
            metadata: [
                'created' => $result->created,
                'updated' => $result->updated,
            ],
        ));

        return $result;
    }

    /**
     * @return list<array{id: int|null, name: string, email: EmailAddress, password: string|null, is_active: bool, roles: list<string>}>
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
            $line = $index + 2;

            $values = [
                'id' => $mapping->value($row, 'id'),
                'name' => $mapping->value($row, 'name'),
                'email' => $mapping->value($row, 'email'),
                'password' => $mapping->value($row, 'password'),
                'is_active' => $mapping->value($row, 'is_active'),
                'roles' => $mapping->value($row, 'roles'),
            ];

            $isUpdate = $values['id'] !== '';

            $validator = Validator::make([
                'id' => $values['id'] === '' ? null : $values['id'],
                'name' => $values['name'] === '' ? null : $values['name'],
                'email' => $values['email'] === '' ? null : $values['email'],
                'password' => $values['password'] === '' ? null : $values['password'],
                'is_active' => $values['is_active'] === '' ? '1' : $values['is_active'],
                'roles' => $this->roleNames($values['roles']),
            ], [
                'id' => ['nullable', 'integer', Rule::exists('users', 'id')],
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'max:255',
                    new EmailAddressRule,
                    Rule::unique('users', 'email')->ignore($values['id'] === '' ? null : $values['id']),
                ],
                // An existing account keeps its password when the column is left empty.
                'password' => [$isUpdate ? 'nullable' : 'required', 'string'],
                'is_active' => ['boolean'],
                'roles' => ['array'],
                'roles.*' => ['string', Rule::exists('roles', 'name')],
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
                'id' => $isUpdate ? (int) $values['id'] : null,
                'name' => $values['name'],
                'email' => $email,
                'password' => $values['password'] === '' ? null : $values['password'],
                'is_active' => filter_var(
                    $values['is_active'] === '' ? '1' : $values['is_active'],
                    FILTER_VALIDATE_BOOLEAN,
                ),
                'roles' => $this->roleNames($values['roles']),
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function roleNames(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(ImportTemplates::ROLE_SEPARATOR, $value))));
    }
}
