@php
    /*
    | What a client has answered, on its own page.
    |
    | Only active attributes appear: deactivating one hides it everywhere while keeping
    | the answers, so they come back untouched if it is switched on again.
    |
    | A basic answer is one line of text. Repeating rows have a shape worth showing, so
    | they are drawn by `attribute-value-rows` rather than folded onto a line.
    */
    $clientAttributes ??= collect();
    $clientAttributeValues ??= [];

    /*
    | An answer holding nothing — a field cleared, a repeater whose rows were all removed
    | — is left out rather than shown as a heading with a blank under it. `formatValue()`
    | already decides that question for every shape, so it decides it here too.
    */
    $answered = $clientAttributes->filter(function ($attribute) use ($clientAttributeValues): bool {
        $value = $clientAttributeValues[$attribute->getKey()] ?? null;

        return $value !== null && $attribute->formatValue($value) !== '';
    });
@endphp

@if ($answered->isNotEmpty())
    <x-card :title="__('client_attributes.client.title')">
        <x-detail-list>
            @foreach ($answered as $attribute)
                @php ($value = $clientAttributeValues[$attribute->getKey()])

                <x-detail-item :label="$attribute->name" :wide="$attribute->type->usesFields()">
                    @if ($attribute->type->usesFields())
                        @include('clients.partials.attribute-value-rows', [
                            'fields' => $attribute->fields,
                            'rows' => $value,
                            'level' => 1,
                        ])
                    @else
                        <p class="whitespace-pre-line">{{ $attribute->formatValue($value) }}</p>
                    @endif
                </x-detail-item>
            @endforeach
        </x-detail-list>
    </x-card>
@endif
