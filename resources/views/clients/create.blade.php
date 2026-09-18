<x-app-layout :heading="__('clients.create.title')" :back="route('clients.index')" :back-label="__('clients.title')" width="narrow">
    <x-page-header :description="__('clients.create.description')" />

    <form method="POST" action="{{ route('clients.store') }}" class="space-y-6">
        @csrf

        <x-card :title="__('clients.create.details')">
            @include('clients.partials.fields')
        </x-card>

        <x-card :title="__('clients.create.defaults')" :description="__('clients.create.defaults_description')">
            {{-- Messages about the set of schedules, rather than one row's start date. --}}
            <x-form.error :name="['schedules', 'schedules.*.default_id']" class="mb-4" />

            @forelse ($defaultNotifications as $index => $default)
                @php
                    $applyField = "schedules[{$index}][apply]";
                    $startsAtField = "schedules[{$index}][starts_at]";
                    $enabledField = "schedules[{$index}][is_enabled]";
                    $startsAtKey = "schedules.{$index}.starts_at";
                    $enabledKey = "schedules.{$index}.is_enabled";
                @endphp

                <div class="border-b border-border py-4 first:pt-0 last:border-0 last:pb-0"
                     x-data="{ apply: {{ old("schedules.{$index}.apply") ? 'true' : 'false' }} }">
                    <input type="hidden" name="schedules[{{ $index }}][default_id]" value="{{ $default->id }}">

                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="space-y-1">
                            <p class="text-label">{{ $default->template->label() }}</p>
                            <p class="text-meta text-foreground-muted">{{ $default->frequency->label() }}</p>
                        </div>

                        <label class="flex items-center gap-2 text-body" for="schedules-{{ $index }}-apply">
                            <input type="hidden" name="{{ $applyField }}" value="0">
                            <input type="checkbox"
                                   id="schedules-{{ $index }}-apply"
                                   name="{{ $applyField }}"
                                   value="1"
                                   x-model="apply"
                                   class="focus-ring size-4 rounded border-border-strong bg-surface accent-primary">
                            {{ __('clients.create.apply') }}
                        </label>
                    </div>

                    <div x-show="apply" x-cloak class="mt-4 grid gap-4 sm:grid-cols-2">
                        <x-form.field :name="$startsAtField"
                                      :error-key="$startsAtKey"
                                      :label="__('cadence.fields.starts_at')"
                                      :hint="__('common.form.timezone_hint', ['timezone' => $viewerTimezone])"
                                      required>
                            <x-form.input :name="$startsAtField"
                                          :id="'schedules-'.$index.'-starts-at'"
                                          type="datetime-local"
                                          :value="old($startsAtKey)" />
                        </x-form.field>

                        <div class="flex items-end pb-2">
                            <x-form.checkbox :name="$enabledField"
                                             :error-key="$enabledKey"
                                             :id="'schedules-'.$index.'-is-enabled'"
                                             :label="__('clients.create.enabled')"
                                             :checked="old($enabledKey, true)" />
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-body text-foreground-muted">{{ __('clients.create.defaults_empty') }}</p>
            @endforelse
        </x-card>

        <x-form.actions>
            <x-button :href="route('clients.index')" variant="secondary" type="button">
                {{ __('common.actions.cancel') }}
            </x-button>

            <x-button type="submit">{{ __('clients.create.submit') }}</x-button>
        </x-form.actions>
    </form>
</x-app-layout>
