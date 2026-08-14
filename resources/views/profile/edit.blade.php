@php
    use App\Enums\ColorScheme;
    use App\Enums\Locale;
    use App\Enums\Theme;

    $sections = [
        ['id' => 'account', 'label' => __('profile.sections.account')],
        ['id' => 'preferences', 'label' => __('profile.sections.preferences')],
        ['id' => 'password', 'label' => __('profile.sections.password')],
        ['id' => 'roles', 'label' => __('profile.sections.roles')],
    ];
@endphp

<x-app-layout :heading="__('profile.title')">
    <x-page-header :description="__('profile.description')" />

    <x-sectioned-page :sections="$sections" :nav-label="__('support.on_this_page')">
        <x-card id="account"
                class="scroll-mt-24"
                :title="__('profile.sections.account')"
                :description="__('profile.sections.account_description')">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.field name="name" :label="__('profile.fields.name')" required>
                        <x-form.input name="name" :value="old('name', $user->name)" required maxlength="255" />
                    </x-form.field>

                    <x-form.field name="email_display" :label="__('profile.fields.email')" :hint="__('profile.hints.email')" error-key="">
                        <x-form.input name="email_display" type="email" :value="$user->email->value" disabled />
                    </x-form.field>
                </div>

                <div class="flex justify-end">
                    <x-button type="submit">{{ __('profile.submit.account') }}</x-button>
                </div>
            </form>
        </x-card>

        <x-card id="preferences"
                class="scroll-mt-24"
                :title="__('profile.sections.preferences')"
                :description="__('profile.sections.preferences_description')">
            {{--
                One preference per row: each of these is a decision on its own, and the
                theme and language options carry enough text that a half-width control
                would truncate them.
            --}}
            <form method="POST" action="{{ route('profile.preferences.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="space-y-5">
                    {{--
                        The blank option is the default one: leaving it selected follows
                        whatever theme the application is set to, including later changes.
                    --}}
                    <x-form.field name="theme"
                                  :label="__('profile.fields.theme')"
                                  :hint="__('profile.hints.theme', ['theme' => $applicationTheme->label()])">
                        <x-form.select name="theme"
                                       :placeholder="__('profile.theme_default')"
                                       :options="collect(Theme::cases())->mapWithKeys(fn (Theme $theme) => [$theme->value => $theme->label().' — '.$theme->description()])"
                                       :selected="old('theme', $user->theme?->value)" />
                    </x-form.field>

                    <x-form.field name="color_scheme" :label="__('settings.color_scheme.label')" required>
                        <x-form.select name="color_scheme"
                                       :options="collect(ColorScheme::cases())->mapWithKeys(fn (ColorScheme $scheme) => [$scheme->value => $scheme->label()])"
                                       :selected="old('color_scheme', $user->color_scheme->value)" />
                    </x-form.field>

                    <x-form.field name="locale" :label="__('profile.fields.locale')" required>
                        <x-form.select name="locale"
                                       :options="collect(Locale::cases())->mapWithKeys(fn (Locale $locale) => [$locale->value => $locale->label()])"
                                       :selected="old('locale', $user->locale->value)" />
                    </x-form.field>

                    <x-form.field name="timezone"
                                  :label="__('profile.fields.timezone')"
                                  :hint="__('profile.hints.timezone')"
                                  required>
                        <x-form.select name="timezone"
                                       :options="collect($timezones)->mapWithKeys(fn (string $timezone) => [$timezone => $timezone])"
                                       :selected="old('timezone', $user->timezone->value)" />
                    </x-form.field>
                </div>

                <div class="flex justify-end">
                    <x-button type="submit">{{ __('profile.submit.preferences') }}</x-button>
                </div>
            </form>
        </x-card>

        <x-card id="password"
                class="scroll-mt-24"
                :title="__('profile.sections.password')"
                :description="__('profile.sections.password_description')">
            <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.field name="current_password" :label="__('profile.fields.current_password')" required class="sm:col-span-2">
                        <x-form.input name="current_password" type="password" autocomplete="current-password" required />
                    </x-form.field>

                    <x-form.field name="password" :label="__('profile.fields.password')" required>
                        <x-form.input name="password" type="password" autocomplete="new-password" required />
                    </x-form.field>

                    <x-form.field name="password_confirmation" :label="__('profile.fields.password_confirmation')" required>
                        <x-form.input name="password_confirmation" type="password" autocomplete="new-password" required />
                    </x-form.field>
                </div>

                <div class="flex justify-end">
                    <x-button type="submit">{{ __('profile.submit.password') }}</x-button>
                </div>
            </form>
        </x-card>

        <x-card id="roles"
                class="scroll-mt-24"
                :title="__('profile.sections.roles')"
                :description="__('profile.sections.roles_description')">
            @if ($user->roles->isEmpty())
                <p class="text-body text-foreground-muted">{{ __('profile.no_roles') }}</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($user->roles as $role)
                        <x-badge variant="info">{{ $role->name }}</x-badge>
                    @endforeach
                </div>
            @endif
        </x-card>
    </x-sectioned-page>
</x-app-layout>
