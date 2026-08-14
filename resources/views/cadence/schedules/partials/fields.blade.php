@php
    use App\Enums\ClientEmailTemplate;
    use App\Enums\ClientNotificationFrequency;

    /** @var \App\Models\ClientNotificationSchedule|null $schedule */
    $schedule ??= null;
    $startsAtValue ??= null;
    $selectedTemplate = old('template', $schedule?->template?->value ?? ClientEmailTemplate::cases()[0]->value);
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    {{-- The chosen template is held in Alpine so it can be previewed as it is picked. --}}
    <div x-data="{ template: '{{ $selectedTemplate }}' }">
        <x-form.field name="template" :label="__('cadence.fields.template')" :hint="__('cadence.hints.template')" required>
            <div class="flex items-center gap-2">
                <x-form.select name="template"
                               x-model="template"
                               :options="collect(ClientEmailTemplate::cases())->mapWithKeys(fn (ClientEmailTemplate $template) => [$template->value => $template->label()])"
                               :selected="$selectedTemplate" />

                <x-button type="button"
                          variant="secondary"
                          icon="eye"
                          class="shrink-0"
                          x-on:click="$dispatch('preview-template', { template: template })">
                    {{ __('cadence.templates.preview') }}
                </x-button>
            </div>
        </x-form.field>
    </div>

    <x-form.field name="frequency" :label="__('cadence.fields.frequency')" :hint="__('cadence.hints.frequency')" required>
        <x-form.select name="frequency"
                       :options="collect(ClientNotificationFrequency::cases())->mapWithKeys(fn (ClientNotificationFrequency $frequency) => [$frequency->value => $frequency->label()])"
                       :selected="old('frequency', $schedule?->frequency?->value)" />
    </x-form.field>

    <x-form.field name="starts_at"
                  :label="__('cadence.fields.starts_at')"
                  :hint="__('cadence.hints.starts_at', ['timezone' => $viewerTimezone])"
                  required
                  class="sm:col-span-2">
        <x-form.input name="starts_at" type="datetime-local" :value="old('starts_at', $startsAtValue)" required />
    </x-form.field>

    <div class="sm:col-span-2">
        <p class="mb-3 text-meta text-foreground-muted">{{ __('cadence.hints.starts_at_anchor') }}</p>

        <x-form.checkbox name="is_enabled"
                         :label="__('cadence.fields.is_enabled')"
                         :hint="__('cadence.hints.is_enabled')"
                         :checked="old('is_enabled', $schedule?->is_enabled ?? true)" />
    </div>
</div>

<x-template-preview-dialog :previews="$templatePreviews" />
