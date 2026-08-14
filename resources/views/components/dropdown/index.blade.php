@props([
    'align' => 'right',
    'width' => 'w-56',
    'triggerClass' => 'btn btn-ghost btn-icon',
    'label' => null,
])

<div class="relative" x-data="{ open: false }" x-on:keydown.escape="open = false">
    <button type="button"
            class="{{ $triggerClass }}"
            x-on:click="open = ! open"
            x-bind:aria-expanded="open ? 'true' : 'false'"
            aria-haspopup="true"
            @if ($label) aria-label="{{ $label }}" @endif>
        {{ $trigger }}
    </button>

    {{-- The panel clips its content so rows and dividers can run edge to edge. --}}
    <div x-show="open"
         x-cloak
         x-on:click.outside="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute z-40 mt-2 {{ $width }} {{ $align === 'right' ? 'right-0 origin-top-right' : 'left-0 origin-top-left' }} overflow-hidden rounded-card border border-border bg-surface-elevated shadow-popover"
         role="menu">
        {{ $slot }}
    </div>
</div>
