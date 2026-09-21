@php
    use App\ValueObjects\ClientAttributeField;

    /*
    | What a row of this attribute is made of, on the attribute's own page.
    |
    | The tree is read here rather than written, so a plain recursion draws it — no bounded
    | depth needed on this side. A field that is itself a set of repeating rows carries its
    | own list, indented against a rule, the same way an answer to one is read on a client.
    |
    | The identifier is shown beside every field because it is what a CSV cell and a stored
    | answer are keyed by — and for a field nobody named, the only way to learn it.
    */
@endphp

<ul class="divide-y divide-border">
    @foreach ($fields as $field)
        <li class="space-y-2 py-2 first:pt-0 last:pb-0">
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <p class="{{ $field->isNamed() ? 'text-label' : 'text-label text-foreground-subtle' }}">
                    {{ $field->isNamed() ? $field->name : __('client_attributes.show.unnamed') }}
                </p>

                <p class="font-mono text-meta text-foreground-subtle">{{ $field->key }}</p>

                <p class="text-meta text-foreground-muted">{{ $field->type->label() }}</p>

                @if ($field->isRequired)
                    <x-badge variant="neutral">{{ __('client_attributes.fields.field_required') }}</x-badge>
                @endif
            </div>

            @if ($field->fields !== [])
                <div class="border-l-2 border-border pl-3">
                    @include('admin.client-attributes.partials.field-tree', ['fields' => $field->fields])
                </div>
            @endif
        </li>
    @endforeach
</ul>
