@php
    /*
    | What a client has answered, on its own page.
    |
    | Only active attributes appear: deactivating one hides it everywhere while keeping
    | the answers, so they come back untouched if it is switched on again.
    */
    $clientAttributes ??= collect();
    $clientAttributeValues ??= [];

    $answered = $clientAttributes->filter(
        fn ($attribute) => ($clientAttributeValues[$attribute->getKey()] ?? null) !== null
    );
@endphp

@if ($answered->isNotEmpty())
    <x-card :title="__('client_attributes.client.title')">
        <x-detail-list>
            @foreach ($answered as $attribute)
                <x-detail-item :label="$attribute->name" :wide="$attribute->type->usesFields()">
                    <p class="whitespace-pre-line">{{ $attribute->formatValue($clientAttributeValues[$attribute->getKey()]) }}</p>
                </x-detail-item>
            @endforeach
        </x-detail-list>
    </x-card>
@endif
