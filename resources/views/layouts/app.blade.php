<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme->value }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentTitle.' · '.$applicationName }}</title>

    @include('partials.favicon')

    @include('partials.color-scheme-script', ['colorScheme' => $colorScheme])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background font-sans text-foreground">
<div x-data="{ navigationOpen: false }" x-on:keydown.escape.window="navigationOpen = false">
    {{-- Mobile navigation drawer --}}
    <div x-show="navigationOpen" x-cloak class="lg:hidden">
        <div class="fixed inset-0 z-40 bg-overlay" x-on:click="navigationOpen = false" aria-hidden="true"></div>

        <div x-show="navigationOpen"
             x-trap.noscroll="navigationOpen"
             x-transition:enter="transition duration-200 ease-out"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition duration-150 ease-in"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-0 left-0 z-50 flex w-sidebar flex-col border-r border-sidebar-border bg-sidebar"
             role="dialog"
             aria-modal="true"
             aria-label="{{ __('navigation.aria_label') }}">
            <div class="flex items-center justify-end px-3 pt-3">
                <button type="button" class="btn btn-ghost btn-icon" x-on:click="navigationOpen = false">
                    <x-icon name="x-mark" size="size-5" />
                    <span class="sr-only">{{ __('navigation.close') }}</span>
                </button>
            </div>

            <x-navigation :application-name="$applicationName" />
        </div>
    </div>

    {{--
        Desktop sidebar. The chrome carries the palette's one warm tone, so the cool
        page beside it reads as a different plane without needing a shadow to say so.
    --}}
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-sidebar flex-col border-r border-sidebar-border bg-sidebar lg:flex">
        <x-navigation :application-name="$applicationName" />
    </aside>

    <div class="lg:pl-sidebar">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-border bg-background/80 px-4 backdrop-blur-md sm:px-6">
            <button type="button"
                    class="btn btn-ghost btn-icon -ml-1 shrink-0 lg:hidden"
                    x-on:click="navigationOpen = true"
                    aria-label="{{ __('navigation.open') }}">
                <x-icon name="bars-3" size="size-5" />
            </button>

            {{--
                The page's name, and the way back out of it. The back link is drawn as a
                control that lights up under the pointer rather than being fenced off with
                a rule: a divider next to the heading's own tick was two marks doing one job.
            --}}
            <div class="flex min-w-0 flex-1 items-center gap-3">
                @if ($back)
                    <a href="{{ $back }}"
                       class="focus-ring -ml-1.5 flex shrink-0 items-center gap-1.5 rounded-control px-2 py-1.5 text-meta text-foreground-muted transition hover:bg-surface-muted hover:text-foreground">
                        <x-icon name="chevron-left" size="size-4" />
                        <span class="hidden sm:inline">{{ $backLabel ?? __('common.actions.back') }}</span>
                        <span class="sr-only sm:hidden">{{ $backLabel ?? __('common.actions.back') }}</span>
                    </a>
                @endif

                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="tick" aria-hidden="true"></span>

                    <h1 class="truncate text-section-title">{{ $heading }}</h1>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-1">
                <x-notification-bell />
                <x-user-menu />
            </div>
        </header>

        <main class="mx-auto w-full {{ $containerClass() }} px-4 py-6 sm:px-6 lg:px-8">
            <x-flash />

            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
