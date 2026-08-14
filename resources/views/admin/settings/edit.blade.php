@php
    use App\Enums\Locale;
    use App\Enums\Theme;
@endphp

{{--
    Everything an administrator configures once. Each section carries its own permission,
    so the page shows only what this reader may see and posts each section to its own
    endpoint rather than merging them into one form.
--}}
<x-app-layout :heading="__('settings.title')" width="narrow">
    <x-page-header :description="__('settings.description')" />

    <div class="space-y-6">
    @if ($mayViewSettings)
    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card :title="__('settings.sections.application')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.field name="application_name"
                              :label="__('settings.fields.application_name')"
                              :hint="__('settings.hints.application_name')"
                              required
                              class="sm:col-span-2">
                    <x-form.input name="application_name" :value="old('application_name', $settings->application_name)" required maxlength="255" />
                </x-form.field>

                <x-form.field name="default_locale"
                              :label="__('settings.fields.default_locale')"
                              :hint="__('settings.hints.default_locale')"
                              required>
                    <x-form.select name="default_locale"
                                   :options="collect(Locale::cases())->mapWithKeys(fn (Locale $locale) => [$locale->value => $locale->label()])"
                                   :selected="old('default_locale', $settings->default_locale->value)" />
                </x-form.field>

                <x-form.field name="default_timezone"
                              :label="__('settings.fields.default_timezone')"
                              :hint="__('settings.hints.default_timezone')"
                              required>
                    <x-form.select name="default_timezone"
                                   :options="collect($timezones)->mapWithKeys(fn (string $timezone) => [$timezone => $timezone])"
                                   :selected="old('default_timezone', $settings->default_timezone->value)" />
                </x-form.field>

                <x-form.field name="default_theme"
                              :label="__('settings.fields.default_theme')"
                              :hint="__('settings.hints.default_theme')"
                              required
                              class="sm:col-span-2">
                    <x-form.select name="default_theme"
                                   :options="collect(Theme::cases())->mapWithKeys(fn (Theme $theme) => [$theme->value => $theme->label().' — '.$theme->description()])"
                                   :selected="old('default_theme', $settings->default_theme->value)" />
                </x-form.field>
            </div>
        </x-card>

        <x-card :title="__('settings.sections.client_email')" :description="__('settings.hints.client_email_sender')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.field name="client_email_sender_name" :label="__('settings.fields.client_email_sender_name')" required>
                    <x-form.input name="client_email_sender_name" :value="old('client_email_sender_name', $settings->client_email_sender_name)" required maxlength="255" />
                </x-form.field>

                <x-form.field name="client_email_sender_email" :label="__('settings.fields.client_email_sender_email')" required>
                    <x-form.input name="client_email_sender_email" type="email" :value="old('client_email_sender_email', $settings->client_email_sender_email->value)" required maxlength="255" />
                </x-form.field>
            </div>

            <p class="mt-4 text-meta text-foreground-subtle">{{ __('settings.hints.infrastructure') }}</p>
        </x-card>

        <x-form.actions>
            <x-button type="submit">{{ __('settings.submit') }}</x-button>
        </x-form.actions>
    </form>
    @endif

    @if ($mayViewDefaults)
        @include('admin.settings.partials.defaults')
    @endif
    </div>
</x-app-layout>
