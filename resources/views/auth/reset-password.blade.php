<x-guest-layout :title="__('auth.reset_password.title')">
    <div class="mb-6 space-y-1">
        <h1 class="text-section-title">{{ __('auth.reset_password.heading') }}</h1>
        <p class="text-meta text-foreground-muted">{{ __('auth.reset_password.subheading') }}</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        {{-- The token is not something anyone typed, so its message has no field to sit under. --}}
        <x-form.error name="token" />

        <x-form.field name="email" :label="__('auth.fields.email')" required>
            <x-form.input name="email" type="email" :value="old('email', $email)" autocomplete="username" required />
        </x-form.field>

        <x-form.field name="password" :label="__('auth.fields.password')" required>
            <x-form.input name="password" type="password" autocomplete="new-password" required autofocus />
        </x-form.field>

        <x-form.field name="password_confirmation" :label="__('auth.fields.password_confirmation')" required>
            <x-form.input name="password_confirmation" type="password" autocomplete="new-password" required />
        </x-form.field>

        <x-button type="submit" class="w-full">{{ __('auth.reset_password.submit') }}</x-button>
    </form>
</x-guest-layout>
