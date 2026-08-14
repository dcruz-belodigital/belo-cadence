@props(['previews'])

{{--
    One dialog per page, holding a preview of every template. A trigger anywhere on the
    page opens it for a given template:

        x-on:click="$dispatch('preview-template', { template: <expression> })"
--}}
<div x-data="{ open: false, current: null }"
     x-on:preview-template.window="current = $event.detail.template; open = true">
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
                 class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-card border border-border bg-surface-elevated shadow-popover"
                 role="dialog"
                 aria-modal="true"
                 aria-label="{{ __('cadence.templates.dialog_title') }}">
                @foreach ($previews as $preview)
                    <div x-show="current === '{{ $preview['template']->value }}'" class="flex min-h-0 flex-col">
                        <div class="flex items-start justify-between gap-3 border-b border-border px-4 py-3">
                            <div class="min-w-0">
                                <p class="text-section-title">{{ $preview['template']->label() }}</p>
                                <p class="truncate text-meta text-foreground-muted">
                                    {{ __('cadence.templates.subject') }}: {{ $preview['subject'] }}
                                </p>
                            </div>

                            <button type="button" class="btn btn-ghost btn-icon shrink-0" x-on:click="open = false">
                                <x-icon name="x-mark" size="size-4" />
                                <span class="sr-only">{{ __('common.actions.cancel') }}</span>
                            </button>
                        </div>

                        <div class="min-h-0 overflow-y-auto p-4">
                            <iframe title="{{ $preview['template']->label() }}"
                                    sandbox
                                    class="h-[24rem] w-full rounded-control border border-border bg-white"
                                    srcdoc="{{ $preview['html'] }}"></iframe>

                            <p class="mt-3 text-meta text-foreground-subtle">{{ __('cadence.templates.sample_notice') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </template>
</div>
