<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme->value }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · '.$applicationName : $applicationName }}</title>

    @include('partials.favicon')

    @include('partials.color-scheme-script', ['colorScheme' => $colorScheme])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface-sunken font-sans text-foreground">
{{--
    The sign-in screen is set on the sunken surface with the panel lifted out of it, and
    the wordmark is ranged left rather than centred — the same editorial rhythm the
    application itself uses, met before the reader is even inside it.
--}}
<div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">
        <div class="mb-7 flex items-center gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-card bg-primary text-primary-foreground">
                <x-logo size="size-6" />
            </span>
            <div class="min-w-0">
                <p class="truncate text-page-title">{{ $applicationName }}</p>
                <p class="text-meta text-foreground-muted">{{ __('auth.tagline') }}</p>
            </div>
        </div>

        <div class="rounded-card border border-border bg-surface p-6">
            <x-flash />

            {{ $slot }}
        </div>
    </div>
</div>
</body>
</html>
