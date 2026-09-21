@php
    use App\Enums\ClientAttributeType;
    use App\ValueObjects\ClientAttributeField;

    /*
    | One level of a repeater's answer, as a person reads it on the client page.
    |
    | `formatValue()` folds a whole tree onto one line, which is what a CSV cell and an
    | email need and what a page should not have to read: two levels down it is all
    | brackets and slashes. The field tree is known here, so this partial recurses on
    | itself instead, and each depth is drawn differently so the shape is legible:
    |
    | - the outermost rows are blocks, one card each, labels above their values;
    | - a repeater inside a row is a ruled list against a rule, its values inline after
    |   their labels, because a row that deep holds two or three short answers.
    |
    | `$fields` is what a row is made of, `$rows` what was answered, `$level` the depth.
    */
    $rows = array_values(array_filter(is_array($rows) ? $rows : [], 'is_array'));

    $isOutermost = $level === 1;

    $rowClass = $isOutermost
        ? 'rounded-control border border-border bg-surface-sunken/40 px-3 py-2.5'
        : 'py-2 first:pt-0 last:pb-0';

    $rowBodyClass = $isOutermost
        ? 'grid gap-x-5 gap-y-2.5 sm:grid-cols-2'
        : 'flex flex-wrap items-baseline gap-x-5 gap-y-1';

    $cellClass = $isOutermost ? 'min-w-0 space-y-0.5' : 'flex min-w-0 items-baseline gap-2';

    // Rows of their own, and text that keeps its line breaks, take a line to themselves.
    $wideCellClass = $isOutermost ? 'min-w-0 space-y-1.5 sm:col-span-2' : 'min-w-0 basis-full space-y-1.5';

    /**
     * Whether anything was actually typed in, at any depth. A cell with nothing in it is
     * left out entirely rather than shown as a label with a blank beside it — the same
     * choice the one-line reading makes. A `false` is an answer: a no is not a blank.
     */
    $holdsSomething = function (mixed $value) use (&$holdsSomething): bool {
        if (is_array($value)) {
            foreach ($value as $entry) {
                if ($holdsSomething($entry)) {
                    return true;
                }
            }

            return false;
        }

        return $value !== null && $value !== '';
    };

    /** Figures are set in mono so a column of them lines up, the way every table sets them. */
    $valueClass = fn (ClientAttributeField $field): string => $field->type === ClientAttributeType::Number
        ? 'numeric text-body break-words whitespace-pre-line'
        : 'text-body break-words whitespace-pre-line';

    // A row made of one unnamed field is a plain list of values, so it is read as one.
    $isPlainList = count($fields) === 1
        && ! $fields[0]->isNamed()
        && ! $fields[0]->type->usesFields();
@endphp

@if ($isPlainList)
    <ul class="space-y-0.5">
        @foreach ($rows as $row)
            @php ($value = $row[$fields[0]->key] ?? null)

            @if ($holdsSomething($value))
                <li class="{{ $valueClass($fields[0]) }}">{{ $fields[0]->format($value) }}</li>
            @endif
        @endforeach
    </ul>
@else
    <div class="{{ $isOutermost ? 'space-y-2' : 'divide-y divide-border' }}">
        @foreach ($rows as $row)
            @continue (! $holdsSomething($row))

            <div class="{{ $rowClass }}">
                <div class="{{ $rowBodyClass }}">
                    @foreach ($fields as $field)
                        @php ($cell = $row[$field->key] ?? null)

                        @continue (! $holdsSomething($cell))

                        @if ($field->type->usesFields())
                            <div class="{{ $wideCellClass }}">
                                @if ($field->isNamed())
                                    <p class="text-overline uppercase text-foreground-subtle">{{ $field->name }}</p>
                                @endif

                                <div class="border-l-2 border-border pl-3">
                                    @include('clients.partials.attribute-value-rows', [
                                        'fields' => $field->fields,
                                        'rows' => $cell,
                                        'level' => $level + 1,
                                    ])
                                </div>
                            </div>
                        @else
                            <div class="{{ $field->type === ClientAttributeType::LongText ? $wideCellClass : $cellClass }}">
                                @if ($field->isNamed())
                                    <p class="text-overline whitespace-nowrap uppercase text-foreground-subtle">{{ $field->name }}</p>
                                @endif

                                {{-- A field nobody named stands on its own, the way it was typed. --}}
                                <p class="{{ $valueClass($field) }}">{{ $field->format($cell) }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
