@php
    use App\Enums\ClientEmailTemplate;
    use App\Enums\ClientNotificationFrequency;

    $templateOptions = collect(ClientEmailTemplate::cases())
        ->mapWithKeys(fn (ClientEmailTemplate $template) => [$template->value => $template->label()]);

    $frequencyOptions = collect(ClientNotificationFrequency::cases())
        ->mapWithKeys(fn (ClientNotificationFrequency $frequency) => [$frequency->value => $frequency->label()]);

    $existing = $defaults->map(fn ($default) => [
        'template' => $default->template->value,
        'frequency' => $default->frequency->value,
        'is_enabled_by_default' => $default->is_enabled_by_default,
    ])->values();

    // Old input arrives as strings, and "0" is truthy in JavaScript, so the checkbox
    // state is normalised before it reaches Alpine.
    $rows = collect(old('entries', $existing))->map(fn (array $row): array => [
        'template' => (string) ($row['template'] ?? ''),
        'frequency' => (string) ($row['frequency'] ?? ''),
        'is_enabled_by_default' => filter_var($row['is_enabled_by_default'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ])->values();
@endphp

{{--
    The default notifications, as a section of the settings page. It keeps its own form
    and its own endpoint because it carries its own permission and writes its own audit
    entry — the page is shared, the authority is not.
--}}

<x-alert variant="info">{{ __('settings.defaults.explanation') }}</x-alert>

<form method="POST" action="{{ route('admin.default-client-notifications.update') }}"
      x-data="{
          rows: {{ Js::from($rows) }},
          add() {
              this.rows.push({ template: '{{ ClientEmailTemplate::cases()[0]->value }}', frequency: '{{ ClientNotificationFrequency::Monthly->value }}', is_enabled_by_default: true });
          },
          remove(index) {
              this.rows.splice(index, 1);
          },
      }">
    @csrf
    @method('PUT')

    <x-card :title="__('settings.defaults.title')">
        {{-- The rows are rendered by Alpine, so their messages cannot sit under a field. --}}
        <x-form.error :name="['entries', 'entries.*']" class="mb-4" />

        <template x-if="rows.length === 0">
            <div class="py-6 text-center">
                <p class="text-label">{{ __('settings.defaults.empty') }}</p>
                <p class="mt-1 text-meta text-foreground-muted">{{ __('settings.defaults.empty_description') }}</p>
            </div>
        </template>

        <div class="space-y-4">
            <template x-for="(row, index) in rows" x-bind:key="index">
                <div class="grid gap-4 border-b border-border pb-4 last:border-0 last:pb-0 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                    <div class="space-y-1.5">
                        <label class="block text-label" x-bind:for="'entry-template-' + index">
                            {{ __('settings.defaults.columns.template') }}
                        </label>

                        <div class="flex items-center gap-2">
                            <select class="form-control"
                                    x-bind:id="'entry-template-' + index"
                                    x-bind:name="'entries[' + index + '][template]'"
                                    x-model="row.template">
                                @foreach ($templateOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>

                            <button type="button"
                                    class="btn btn-secondary btn-md shrink-0"
                                    x-on:click="$dispatch('preview-template', { template: row.template })">
                                <x-icon name="eye" size="size-4" />
                                <span class="sr-only">{{ __('cadence.templates.preview') }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-label" x-bind:for="'entry-frequency-' + index">
                            {{ __('settings.defaults.columns.frequency') }}
                        </label>
                        <select class="form-control"
                                x-bind:id="'entry-frequency-' + index"
                                x-bind:name="'entries[' + index + '][frequency]'"
                                x-model="row.frequency">
                            @foreach ($frequencyOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center justify-between gap-4 pb-2 sm:justify-end">
                        <label class="flex items-center gap-2 text-body">
                            <input type="hidden" x-bind:name="'entries[' + index + '][is_enabled_by_default]'" value="0">
                            <input type="checkbox"
                                   x-bind:name="'entries[' + index + '][is_enabled_by_default]'"
                                   value="1"
                                   x-model="row.is_enabled_by_default"
                                   class="focus-ring size-4 rounded border-border-strong bg-surface accent-primary">
                            {{ __('settings.defaults.columns.enabled') }}
                        </label>

                        <button type="button"
                                class="btn btn-ghost btn-sm text-danger"
                                x-on:click="remove(index)">
                            {{ __('settings.defaults.remove') }}
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-5">
            <x-button type="button" variant="secondary" size="sm" icon="plus" x-on:click="add()">
                {{ __('settings.defaults.add') }}
            </x-button>
        </div>
    </x-card>

    <x-form.actions class="mt-6">
        <x-button type="submit">{{ __('settings.defaults.submit') }}</x-button>
    </x-form.actions>
</form>

<x-template-preview-dialog :previews="$templatePreviews" />
