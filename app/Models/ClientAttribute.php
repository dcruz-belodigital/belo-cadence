<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsClientAttributeChoices;
use App\Casts\AsClientAttributeFields;
use App\Data\ClientAttributes\ClientAttributeFilters;
use App\Enums\ClientAttributeType;
use App\ValueObjects\ClientAttributeChoices;
use App\ValueObjects\ClientAttributeField;
use Carbon\CarbonImmutable;
use Database\Factories\ClientAttributeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A field a team records about a client, beyond the four the clients table holds.
 *
 * Definitions are data rather than source, so adding one is a row. `key` and `type` are
 * settled at creation and never change: `key` is what a CSV column is called, and
 * retyping would silently invalidate every value already stored.
 *
 * Retiring one is `is_active = false`, which hides it everywhere while keeping its
 * values; deleting one really does destroy them, which is why the policy asks first.
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property ClientAttributeType $type
 * @property string|null $hint
 * @property ClientAttributeChoices $options
 * @property list<ClientAttributeField> $fields
 * @property bool $is_required
 * @property bool $is_active
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Collection<int, ClientAttributeValue> $values
 * @property-read int|null $values_count
 */
#[Fillable(['key', 'name', 'type', 'hint', 'options', 'fields', 'is_required', 'is_active', 'position'])]
final class ClientAttribute extends Model
{
    /** @use HasFactory<ClientAttributeFactory> */
    use HasFactory;

    /**
     * How deeply repeaters may nest.
     *
     * Everything that reads the tree recurses without a limit; the cap exists because the
     * definition editor has to draw the tree before it is known, and Alpine has no
     * recursive components — so the editor emits this many levels of markup.
     */
    public const MAX_DEPTH = 3;

    /**
     * Column names a definition may not take, or its CSV column would shadow a real one.
     *
     * @var list<string>
     */
    public const RESERVED_KEYS = ['id', 'name', 'email', 'status', 'notes'];

    /**
     * @return HasMany<ClientAttributeValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(ClientAttributeValue::class);
    }

    /**
     * The definitions that appear on a form, in the order somebody chose for them.
     *
     * Every surface reads this one scope — the client form, the detail page, both export
     * modes and the import template — so none of them can disagree about which
     * attributes exist or what order they come in.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->ordered();
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /**
     * Applies the attribute table's search, filters and sorting.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function filtered(Builder $query, ClientAttributeFilters $filters): void
    {
        $query
            ->when($filters->search, fn (Builder $attributes, string $search): Builder => $attributes->where(
                fn (Builder $match): Builder => $match
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%")
            ))
            ->when($filters->type, fn (Builder $attributes, ClientAttributeType $type): Builder => $attributes->where('type', $type))
            ->when($filters->isActive !== null, fn (Builder $attributes): Builder => $attributes->where('is_active', $filters->isActive))
            ->orderBy($filters->sort, $filters->direction)
            ->orderBy('id');
    }

    /**
     * The sub-field a binding path points at, or null when the path has gone stale.
     *
     * @param  list<string>  $path
     */
    public function findField(array $path): ?ClientAttributeField
    {
        if ($path === []) {
            return null;
        }

        $fields = $this->fields;
        $found = null;

        foreach ($path as $key) {
            $found = null;

            foreach ($fields as $field) {
                if ($field->key === $key) {
                    $found = $field;

                    break;
                }
            }

            if (! $found instanceof ClientAttributeField) {
                return null;
            }

            $fields = $found->fields;
        }

        return $found;
    }

    /**
     * The definition flattened to plain values, for the audit log.
     *
     * The audit columns cast to a plain array, so enums and value objects are unwrapped
     * here rather than left to json_encode — the same shape a client's own audit entry
     * is built in.
     *
     * @return array<string, mixed>
     */
    public function auditShape(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'type' => $this->type->value,
            'hint' => $this->hint,
            'options' => $this->options->activeValues(),
            'fields' => ClientAttributeField::listToArray($this->fields),
            'is_required' => $this->is_required,
            'is_active' => $this->is_active,
            'position' => $this->position,
        ];
    }

    /**
     * Everything an email template slot could be bound to on this attribute.
     *
     * A basic attribute offers itself. A repeater offers itself — a template can take a
     * whole repeater and render its rows — and every field inside it, at every depth, so
     * "use Contacts, and from it Name" is one entry in an ordinary select rather than a
     * tree widget.
     *
     * A path is *repeated* when anything along it is a repeater, which is what decides
     * whether it may fill a slot that expects one value or one that expects a list.
     *
     * @return list<array{path: list<string>, label: string, type: ClientAttributeType, repeated: bool}>
     */
    public function bindablePaths(): array
    {
        $paths = [[
            'path' => [],
            'label' => $this->name,
            'type' => $this->type,
            'repeated' => false,
        ]];

        if ($this->type->usesFields()) {
            $paths = [...$paths, ...$this->pathsInto($this->fields, [], $this->name)];
        }

        return $paths;
    }

    /**
     * What a binding path points at for one client's answer.
     *
     * A path through a repeater yields every row's value, flattened, because that is what
     * "use repeater Contacts, field Name" means. A path that resolves to nothing at all
     * comes back as an empty list, which is how a slot knows it cannot be filled.
     *
     * @param  list<string>  $path
     * @return list<mixed>
     */
    public function valuesAt(mixed $answer, array $path): array
    {
        if ($answer === null) {
            return [];
        }

        if ($path === []) {
            return [$answer];
        }

        [$key, $rest] = [$path[0], array_slice($path, 1)];

        $values = [];

        foreach (is_array($answer) ? $answer : [] as $row) {
            if (! is_array($row) || ! array_key_exists($key, $row)) {
                continue;
            }

            $cell = $row[$key];

            foreach ($rest === [] ? [$cell] : $this->valuesAt($cell, $rest) as $value) {
                if ($value !== null && $value !== '' && $value !== []) {
                    $values[] = $value;
                }
            }
        }

        return $values;
    }

    /**
     * @param  list<ClientAttributeField>  $fields
     * @param  list<string>  $prefix
     * @return list<array{path: list<string>, label: string, type: ClientAttributeType, repeated: bool}>
     */
    private function pathsInto(array $fields, array $prefix, string $label): array
    {
        $paths = [];

        foreach ($fields as $field) {
            $path = [...$prefix, $field->key];
            $name = $label.' → '.$field->name;

            $paths[] = [
                'path' => $path,
                'label' => $name,
                'type' => $field->type,
                // Anything reached through a repeater is many values, not one.
                'repeated' => true,
            ];

            if ($field->type->usesFields()) {
                $paths = [...$paths, ...$this->pathsInto($field->fields, $path, $name)];
            }
        }

        return $paths;
    }

    /**
     * An empty repeater row, filled in at every depth, for the form to clone.
     *
     * @return array<string, mixed>
     */
    public function blankRow(): array
    {
        return ClientAttributeField::blankRow($this->fields);
    }

    /**
     * The value as a person reads it: on the client page, and in the table-mode CSV.
     */
    public function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return $this->type->usesFields()
            ? ClientAttributeField::formatRows(is_array($value) ? $value : [], $this->fields)
            : $this->type->format($value);
    }

    /**
     * The value as the machine-readable CSV writes it.
     *
     * A repeater is JSON because it is the one encoding with no escaping ambiguity, and
     * because it nests for free.
     */
    public function toCsv(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($this->type->usesFields()) {
            return (string) json_encode(is_array($value) ? array_values($value) : []);
        }

        return $this->type->toCsv($value);
    }

    /**
     * What a cell for this attribute looks like, for the import template.
     */
    public function csvExample(): string
    {
        return match ($this->type) {
            ClientAttributeType::Text => 'Example',
            ClientAttributeType::LongText => 'A longer note',
            ClientAttributeType::Number => '12',
            ClientAttributeType::Date => '2026-03-01',
            ClientAttributeType::Boolean => '1',
            ClientAttributeType::Select => $this->options->activeValues()[0] ?? '',
            ClientAttributeType::Url => 'https://example.com',
            ClientAttributeType::Email => 'person@example.com',
            ClientAttributeType::Repeater => (string) json_encode([ClientAttributeField::blankRow($this->fields)]),
        };
    }

    /**
     * A CSV cell turned back into a value.
     *
     * A repeater cell that is not valid JSON is handed back as the string it was, so the
     * rule reports it against its line rather than letting it decode to an empty list.
     */
    public function fromCsv(string $cell): mixed
    {
        $cell = trim($cell);

        if ($cell === '') {
            return null;
        }

        if (! $this->type->usesFields()) {
            return $this->type->normalise($cell);
        }

        $decoded = json_decode($cell, true);

        return is_array($decoded) ? array_values($decoded) : $cell;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ClientAttributeType::class,
            'options' => AsClientAttributeChoices::class,
            'fields' => AsClientAttributeFields::class,
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
