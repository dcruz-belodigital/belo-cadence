@props([
    'action',
    'method' => 'DELETE',
    'title',
    'message',
    'confirm' => null,
    'variant' => 'danger',
    'triggerAs' => 'button',
    'triggerVariant' => 'ghost',
    'triggerSize' => 'sm',
    'icon' => null,
])

@php
    $dialogId = 'confirm-dialog-'.substr(md5($action.$title), 0, 10);

    /*
    | The same question asked from two places: a button of its own on a record's page,
    | or a row in an `x-table.actions` menu. Only the trigger changes — a menu entry has
    | to look like the entries around it, and `contents` keeps this wrapper out of the
    | panel's layout while still holding the dialog's state.
    */
    $isMenuItem = $triggerAs === 'menu-item';
@endphp

{{-- One dialog for every irreversible-looking action, so confirmation always behaves the same. --}}
<div x-data="{ open: false }" class="{{ $isMenuItem ? 'contents' : 'inline-flex' }}">
    @if ($isMenuItem)
        <x-dropdown.item type="button" :icon="$icon" :variant="$variant" x-on:click="open = true">
            {{ $trigger }}
        </x-dropdown.item>
    @else
        <x-button type="button" :variant="$triggerVariant" :size="$triggerSize" :icon="$icon" x-on:click="open = true">
            {{ $trigger }}
        </x-button>
    @endif

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center">
            <div x-show="open"
                 x-transition.opacity
                 class="absolute inset-0 bg-overlay"
                 x-on:click="open = false"
                 aria-hidden="true"></div>

            <div x-show="open"
                 x-trap.noscroll="open"
                 x-on:keydown.escape.window="open = false"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-2 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 class="relative w-full max-w-md rounded-card border border-border bg-surface-elevated p-5 shadow-popover"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="{{ $dialogId }}">
                <h2 id="{{ $dialogId }}" class="text-section-title">{{ $title }}</h2>
                <p class="mt-2 text-body text-foreground-muted">{{ $message }}</p>

                <form method="POST" action="{{ $action }}" class="mt-5 flex justify-end gap-2">
                    @csrf
                    @method($method)

                    <x-button type="button" variant="secondary" size="sm" x-on:click="open = false">
                        {{ __('common.actions.cancel') }}
                    </x-button>

                    <x-button type="submit" :variant="$variant" size="sm">
                        {{ $confirm ?? $title }}
                    </x-button>
                </form>
            </div>
        </div>
    </template>
</div>
