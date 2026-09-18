@props(['label' => null])

{{--
    Every action a row offers, behind one trigger, so a table shows its records rather
    than a wall of repeated buttons. Entries are `x-dropdown.item`s, or an
    `x-confirm-form` with `trigger-as="menu-item"` where the action deserves a question.

    It does not reuse `x-dropdown`, which draws its panel in place: a table sits in a
    card that clips to its radius and scrolls sideways, so a menu opened on the last row
    or the trailing column would be cut off. This one teleports the panel to the body and
    places it in viewport coordinates instead, and closes on a scroll or a resize because
    those coordinates are only true where they were measured.
--}}
<div class="inline-flex"
     x-data="{
         open: false,
         top: 0,
         right: 0,
         toggle() {
             this.open = ! this.open;

             if (! this.open) {
                 return;
             }

             const trigger = this.$refs.trigger.getBoundingClientRect();

             this.top = trigger.bottom + 6;
             this.right = window.innerWidth - trigger.right;

             // A row near the foot of the window would otherwise open off the screen.
             this.$nextTick(() => {
                 const height = this.$refs.panel.offsetHeight;

                 this.top = Math.max(8, Math.min(this.top, window.innerHeight - height - 8));
             });
         },
     }"
     x-on:keydown.escape.window="open = false"
     x-on:scroll.window.capture="open = false"
     x-on:resize.window="open = false">
    <button type="button"
            x-ref="trigger"
            class="btn btn-ghost btn-icon"
            x-on:click="toggle()"
            x-bind:aria-expanded="open ? 'true' : 'false'"
            aria-haspopup="true"
            aria-label="{{ $label ?? __('common.actions.more') }}">
        <x-icon name="dots" size="size-4" />
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-40">
            <div class="absolute inset-0" x-on:click="open = false" aria-hidden="true"></div>

            {{-- Choosing an entry closes the menu; a confirmation dialog stands on its own. --}}
            <div x-show="open"
                 x-ref="panel"
                 x-trap="open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-bind:style="'top: ' + top + 'px; right: ' + right + 'px'"
                 x-on:click="open = false"
                 class="absolute w-56 origin-top-right overflow-hidden rounded-card border border-border bg-surface-elevated shadow-popover"
                 role="menu">
                {{ $slot }}
            </div>
        </div>
    </template>
</div>
