@props([
    'action',
    'active' => false,
])

@php
    /*
    | Whether anything in the bar is worth clearing. Hidden fields carry the sort, which
    | clearing keeps, and the submit button has no value of its own — neither counts.
    */
    $anythingToClear = "filled = Array.from(\$el.elements)"
        ."  .some(field => field.name && field.type !== 'hidden' && field.type !== 'submit'"
        ."    && (field.type === 'checkbox' ? field.checked : field.value !== ''))";
@endphp

{{--
    Filters are plain GET parameters, so any filtered view can be linked, bookmarked
    and exported exactly as it is shown.

    Clearing is offered as soon as there is something to clear — including choices that
    have been made but not yet applied, which is the moment someone is most likely to
    change their mind. When filters are already applied the link is rendered visible by
    the server, so it survives without Javascript; the unapplied case is the only part
    that needs Alpine, and it starts hidden.
--}}
<form method="GET"
      action="{{ $action }}"
      x-data="{ filled: {{ $active ? 'true' : 'false' }} }"
      x-on:input="{{ $anythingToClear }}"
      x-on:change="{{ $anythingToClear }}"
      class="mb-4 rounded-card border border-border bg-surface p-4 shadow-card">
    @if (request()->filled('sort'))
        <input type="hidden" name="sort" value="{{ request()->string('sort') }}">
    @endif

    @if (request()->filled('direction'))
        <input type="hidden" name="direction" value="{{ request()->string('direction') }}">
    @endif

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {{ $slot }}
    </div>

    <div class="mt-4 flex items-center gap-2">
        <x-button type="submit" size="sm" icon="filter">{{ __('common.actions.apply_filters') }}</x-button>

        {{--
            The directives sit on a plain wrapper rather than on the component tag,
            because Blade parses a component's attributes itself and a directive in
            among them is not compiled.
        --}}
        <span x-show="filled" @unless ($active) x-cloak @endunless>
            <x-button :href="$action" variant="ghost" size="sm">
                {{ __('common.actions.clear_filters') }}
            </x-button>
        </span>
    </div>
</form>
