@if ($formatted === null)
    <span class="text-foreground-subtle">{{ $placeholder }}</span>
@else
    <time datetime="{{ $iso }}" {{ $attributes->merge(['class' => 'whitespace-nowrap']) }}>
        {{ $formatted }}@if ($withTimezone)<span class="text-foreground-subtle"> ({{ $timezone }})</span>@endif
    </time>
@endif
