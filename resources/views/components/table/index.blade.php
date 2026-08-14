@props(['label' => null])

<div class="overflow-hidden rounded-card border border-border bg-surface shadow-card">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-body" @if ($label) aria-label="{{ $label }}" @endif>
            <thead class="border-b border-border bg-surface-sunken/50">
                <tr>{{ $head }}</tr>
            </thead>

            <tbody class="divide-y divide-border">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($footer)
        {{ $footer }}
    @endisset
</div>
