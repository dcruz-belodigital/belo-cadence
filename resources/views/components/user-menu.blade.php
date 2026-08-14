@php
    use App\Enums\ColorScheme;
    use App\Enums\Locale;

    $user = auth()->user();
    $current = $user->color_scheme;

    $schemeIcons = [
        ColorScheme::System->value => 'monitor',
        ColorScheme::Light->value => 'sun',
        ColorScheme::Dark->value => 'moon',
    ];
@endphp

<x-dropdown :label="__('navigation.account')" width="w-64" trigger-class="user-menu-trigger">
    <x-slot:trigger>
        <span class="flex size-7 shrink-0 items-center justify-center rounded-control bg-primary-soft text-meta font-semibold text-primary-soft-foreground">
            {{ $user->initials() }}
        </span>

        <span class="hidden max-w-36 truncate sm:block">{{ $user->name }}</span>

        <x-icon name="chevron-down" size="size-4" class="text-foreground-muted" />
    </x-slot:trigger>

    <div class="px-3 py-2.5">
        <p class="truncate text-label text-foreground">{{ $user->name }}</p>
        <p class="truncate text-meta text-foreground-muted">{{ $user->email }}</p>
    </div>

    <x-dropdown.divider />

    <x-dropdown.item :href="route('profile.edit')" icon="user-circle">
        {{ __('navigation.profile') }}
    </x-dropdown.item>

    <x-dropdown.divider />

    {{-- Choosing light or dark saves the reader's preference straight away. --}}
    <form method="POST" action="{{ route('profile.color-scheme.update') }}" class="px-3 py-2.5">
        @csrf
        @method('PATCH')

        <div class="flex items-center gap-1 rounded-control bg-surface-muted p-1"
             role="group"
             aria-label="{{ __('settings.color_scheme.label') }}">
            @foreach (ColorScheme::cases() as $scheme)
                <button type="submit"
                        name="color_scheme"
                        value="{{ $scheme->value }}"
                        aria-pressed="{{ $scheme === $current ? 'true' : 'false' }}"
                        title="{{ $scheme->label() }}"
                        class="focus-ring flex h-8 flex-1 items-center justify-center rounded-control transition {{ $scheme === $current
                            ? 'bg-surface text-primary ring-1 ring-border'
                            : 'text-foreground-subtle hover:text-foreground' }}">
                    <x-icon :name="$schemeIcons[$scheme->value]" size="size-4" />
                    <span class="sr-only">{{ $scheme->label() }}</span>
                </button>
            @endforeach
        </div>
    </form>

    <x-dropdown.divider />

    {{--
        The language is a real select, so it opens the browser's own list with the
        current language ticked. It is dressed as a menu row rather than as a form
        control — same padding, same hover, edge to edge — with the chevron drawn over
        it to say that it opens. Changing it saves straight away, like the scheme above.
    --}}
    <form method="POST" action="{{ route('profile.locale.update') }}" class="relative">
        @csrf
        @method('PATCH')

        <label for="account-locale" class="sr-only">{{ __('profile.fields.locale') }}</label>

        <select id="account-locale"
                name="locale"
                x-on:change="$el.form.requestSubmit()"
                class="focus-ring-inset w-full cursor-pointer appearance-none bg-transparent py-2.5 pl-3 pr-9 text-left text-body text-foreground transition hover:bg-surface-muted">
            @foreach (Locale::cases() as $locale)
                <option value="{{ $locale->value }}" @selected($locale === $user->locale)>
                    {{ $locale->flag() }}&nbsp;&nbsp;{{ $locale->label() }}
                </option>
            @endforeach
        </select>

        <x-icon name="chevron-down"
                size="size-4"
                class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-foreground-subtle" />
    </form>

    <x-dropdown.divider />

    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <x-dropdown.item type="submit" icon="logout" variant="danger">
            {{ __('navigation.sign_out') }}
        </x-dropdown.item>
    </form>
</x-dropdown>
