@php
    use App\Enums\EmailTemplate;
    use App\Enums\NotificationFrequency;
    use App\Enums\NotificationTarget;

    /** @var \App\Models\NotificationSchedule|null $schedule */
    $schedule ??= null;
    /** @var \App\Models\Client|null $client */
    $client ??= null;
    $startsAtValue ??= null;
    $clients ??= collect();

    /*
    | A schedule's target is decided once. Creating from a client page fixes it to that
    | client, editing keeps whatever the schedule already is, and only the general create
    | form actually offers the choice.
    */
    $fixedTarget = $schedule?->target ?? ($client !== null ? NotificationTarget::Client : null);
    $target = $fixedTarget ?? NotificationTarget::tryFrom(old('target', '')) ?? NotificationTarget::Client;

    $templatesFor = fn (NotificationTarget $for) => collect(EmailTemplate::for($for))
        ->mapWithKeys(fn (EmailTemplate $template) => [$template->value => $template->label()]);

    $clientTemplates = $templatesFor(NotificationTarget::Client);
    $listTemplates = $templatesFor(NotificationTarget::Recipients);

    $currentTemplate = old('template', $schedule?->template?->value);

    $recipientsValue = old('recipients');
    $recipientsText = is_array($recipientsValue)
        ? implode("\n", $recipientsValue)
        : ($recipientsValue ?? collect($schedule?->recipients ?? [])->implode("\n"));
@endphp

{{--
    The two targets are laid out side by side and the inactive one is disabled rather
    than merely hidden: a disabled field is not submitted, so validation only ever sees
    the fields belonging to the target that was actually chosen.
--}}
<div class="space-y-5"
     x-data="{
        target: '{{ $target->value }}',
        clientTemplate: '{{ $currentTemplate && $clientTemplates->has($currentTemplate) ? $currentTemplate : $clientTemplates->keys()->first() }}',
        listTemplate: '{{ $currentTemplate && $listTemplates->has($currentTemplate) ? $currentTemplate : $listTemplates->keys()->first() }}',
        get template() { return this.target === '{{ NotificationTarget::Client->value }}' ? this.clientTemplate : this.listTemplate },
        get isBlank() { return this.template === '{{ EmailTemplate::Blank->value }}' },
        message: @js(old('message', $schedule?->message ?? '')),
     }">

    @if ($fixedTarget === null)
        <x-form.field name="target" :label="__('cadence.fields.target')" :hint="__('cadence.hints.target')" required>
            @include('cadence.partials.target-options')
        </x-form.field>
    @else
        <input type="hidden" name="target" value="{{ $fixedTarget->value }}">
    @endif

    {{-- A client schedule --}}
    <div x-show="target === '{{ NotificationTarget::Client->value }}'" x-cloak class="grid gap-5 sm:grid-cols-2">
        @if ($client !== null)
            <x-form.field :label="__('cadence.fields.client')">
                <p class="form-control bg-surface-sunken text-foreground-muted">{{ $client->name }}</p>
            </x-form.field>
        @elseif ($schedule?->client !== null)
            <x-form.field :label="__('cadence.fields.client')">
                <p class="form-control bg-surface-sunken text-foreground-muted">{{ $schedule->client->name }}</p>
            </x-form.field>
        @else
            <x-form.field name="client" :label="__('cadence.fields.client')" :hint="__('cadence.hints.client')" required>
                <x-form.select name="client"
                               :options="$clients"
                               :selected="old('client')"
                               :placeholder="__('deliveries.send.choose_client')"
                               x-bind:disabled="target !== '{{ NotificationTarget::Client->value }}'" />
            </x-form.field>
        @endif

        <x-form.field name="template" input-id="client_template" :label="__('cadence.fields.template')" :hint="__('cadence.hints.template')" required>
            <x-template-preview-action preview="template"
                                       disabled="target !== '{{ NotificationTarget::Client->value }}'">
                <x-form.select name="template"
                               id="client_template"
                               x-model="clientTemplate"
                               :options="$clientTemplates"
                               :selected="$clientTemplates->has($currentTemplate) ? $currentTemplate : null"
                               x-bind:disabled="target !== '{{ NotificationTarget::Client->value }}'" />
            </x-template-preview-action>
        </x-form.field>
    </div>

    {{-- A recipient-list schedule --}}
    <div x-show="target === '{{ NotificationTarget::Recipients->value }}'" x-cloak class="grid gap-5 sm:grid-cols-2">
        <x-form.field name="name" :label="__('cadence.fields.name')" :hint="__('cadence.hints.name')" required>
            <x-form.input name="name"
                          :value="old('name', $schedule?->name)"
                          x-bind:disabled="target !== '{{ NotificationTarget::Recipients->value }}'" />
        </x-form.field>

        <x-form.field name="template" input-id="list_template" :label="__('cadence.fields.template')" :hint="__('cadence.hints.template')" required>
            <x-template-preview-action preview="template"
                                       disabled="target !== '{{ NotificationTarget::Recipients->value }}'">
                <x-form.select name="template"
                               id="list_template"
                               x-model="listTemplate"
                               :options="$listTemplates"
                               :selected="$listTemplates->has($currentTemplate) ? $currentTemplate : null"
                               x-bind:disabled="target !== '{{ NotificationTarget::Recipients->value }}'" />
            </x-template-preview-action>
        </x-form.field>

        <x-form.field name="recipients"
                      :label="__('cadence.fields.recipients')"
                      :hint="__('cadence.hints.recipients')"
                      :error-key="['recipients', 'recipients.*']"
                      required
                      class="sm:col-span-2">
            <x-form.textarea name="recipients"
                             rows="4"
                             x-bind:disabled="target !== '{{ NotificationTarget::Recipients->value }}'">{{ $recipientsText }}</x-form.textarea>
        </x-form.field>
    </div>

    {{-- The blank template brings no wording of its own, so it is written here. --}}
    <div x-show="isBlank" x-cloak class="grid gap-5">
        <x-form.field name="subject" :label="__('cadence.fields.subject')" :hint="__('cadence.hints.subject')" required>
            <x-form.input name="subject" :value="old('subject', $schedule?->subject)" x-bind:disabled="! isBlank" />
        </x-form.field>

        <x-form.field name="message" :label="__('cadence.fields.message')" :hint="__('cadence.hints.message')" required>
            <x-form.textarea name="message" rows="6" x-model="message" x-bind:disabled="! isBlank">{{ old('message', $schedule?->message) }}</x-form.textarea>
        </x-form.field>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-form.field name="frequency" :label="__('cadence.fields.frequency')" :hint="__('cadence.hints.frequency')" required>
            <x-form.select name="frequency"
                           :options="collect(NotificationFrequency::cases())->mapWithKeys(fn (NotificationFrequency $frequency) => [$frequency->value => $frequency->label()])"
                           :selected="old('frequency', $schedule?->frequency?->value)" />
        </x-form.field>

        <x-form.field name="starts_at"
                      :label="__('cadence.fields.starts_at')"
                      :hint="__('common.form.timezone_hint', ['timezone' => $viewerTimezone])"
                      required>
            <x-form.input name="starts_at" type="datetime-local" :value="old('starts_at', $startsAtValue)" required />
        </x-form.field>
    </div>

    @include('cadence.partials.template-slots')

    <div>
        <p class="mb-3 text-meta text-foreground-muted">{{ __('cadence.hints.starts_at_anchor') }}</p>

        <x-form.checkbox name="is_enabled"
                         :label="__('cadence.fields.is_enabled')"
                         :hint="__('cadence.hints.is_enabled')"
                         :checked="old('is_enabled', $schedule?->is_enabled ?? true)" />
    </div>
</div>

<x-template-preview-dialog :previews="$templatePreviews" />
