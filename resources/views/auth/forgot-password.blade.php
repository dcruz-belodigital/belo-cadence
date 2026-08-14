<x-guest-layout :title="__('auth.forgot_password.title')">
    <div class="mb-6 space-y-1">
        <h1 class="text-section-title">{{ __('auth.forgot_password.heading') }}</h1>
        <p class="text-meta text-foreground-muted">{{ __('auth.forgot_password.subheading') }}</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <x-form.field name="email" :label="__('auth.fields.email')" required>
            <x-form.input name="email" type="email" :value="old('email')" autocomplete="username" required autofocus />
        </x-form.field>

        <x-button type="submit" class="w-full">{{ __('auth.forgot_password.submit') }}</x-button>

        <a href="{{ route('login') }}" class="focus-ring block rounded-control text-center text-meta text-foreground-muted transition hover:text-foreground">
            {{ __('auth.forgot_password.back_to_login') }}
        </a>
    </form>
</x-guest-layout>
