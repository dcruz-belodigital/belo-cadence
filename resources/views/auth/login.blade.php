<x-guest-layout :title="__('auth.login.title')">
    <div class="mb-6 space-y-1">
        <h1 class="text-section-title">{{ __('auth.login.heading') }}</h1>
        <p class="text-meta text-foreground-muted">{{ __('auth.login.subheading') }}</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf

        <x-form.field name="email" :label="__('auth.fields.email')" required>
            <x-form.input name="email" type="email" :value="old('email')" autocomplete="username" required autofocus />
        </x-form.field>

        <x-form.field name="password" :label="__('auth.fields.password')" required>
            <x-form.input name="password" type="password" autocomplete="current-password" required />
        </x-form.field>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-form.checkbox name="remember" :label="__('auth.login.remember')" :checked="old('remember')" />

            <a href="{{ route('password.request') }}" class="focus-ring rounded-control text-meta text-primary transition hover:underline">
                {{ __('auth.login.forgot') }}
            </a>
        </div>

        <x-button type="submit" class="w-full">{{ __('auth.login.submit') }}</x-button>
    </form>
</x-guest-layout>
