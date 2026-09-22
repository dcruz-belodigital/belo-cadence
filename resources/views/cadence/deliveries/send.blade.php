@php
    use App\Enums\EmailTemplate;
    use App\Enums\NotificationTarget;
    use App\Models\NotificationDelivery;

    $target = NotificationTarget::tryFrom(old('target', '')) ?? NotificationTarget::Client;

    $templatesFor = fn (NotificationTarget $for) => collect(EmailTemplate::for($for))
        ->mapWithKeys(fn (EmailTemplate $template) => [$template->value => $template->label()]);

    $clientTemplates = $templatesFor(NotificationTarget::Client);
    $listTemplates = $templatesFor(NotificationTarget::Recipients);
    $currentTemplate = old('template');

    $recipientsValue = old('recipients');
    $recipientsText = is_array($recipientsValue) ? implode("\n", $recipientsValue) : ($recipientsValue ?? '');

    // Sending and reading history are separate permissions, so the way back is only
    // offered to somebody who may actually follow it.
    $back = auth()->user()->can('viewAny', NotificationDelivery::class)
        ? route('cadence.deliveries.index')
        : null;
@endphp

<x-app-layout :heading="__('deliveries.send.title')"
               :back="$back"
               :back-label="__('deliveries.title')"
               width="narrow">
    <x-page-header :description="__('deliveries.send.description')" />

    <form method="POST" action="{{ route('cadence.deliveries.send.store') }}" class="space-y-6">
        @csrf

        {{--
            The inactive target's fields are disabled rather than merely hidden: a disabled
            field is not submitted, so validation only sees the target actually chosen.
        --}}
        <x-card :title="__('deliveries.show.details')">
            <div class="space-y-5"
                 x-data="{
                    target: '{{ $target->value }}',
                    clientTemplate: '{{ $currentTemplate && $clientTemplates->has($currentTemplate) ? $currentTemplate : $clientTemplates->keys()->first() }}',
                    listTemplate: '{{ $currentTemplate && $listTemplates->has($currentTemplate) ? $currentTemplate : $listTemplates->keys()->first() }}',
                    get template() { return this.target === '{{ NotificationTarget::Client->value }}' ? this.clientTemplate : this.listTemplate },
                    get isBlank() { return this.template === '{{ EmailTemplate::Blank->value }}' },
                    message: @js(old('message', '')),
                 }">

                <x-form.field name="target" :label="__('cadence.fields.target')" required>
                    @include('cadence.partials.target-options')
                </x-form.field>

                {{-- To a client --}}
                <div x-show="target === '{{ NotificationTarget::Client->value }}'" x-cloak class="grid gap-5 sm:grid-cols-2">
                    @if ($clients->isEmpty())
                        <div class="sm:col-span-2">
                            <x-alert variant="warning" :title="__('deliveries.send.no_clients')">
                                {{ __('deliveries.send.no_clients_description') }}
                            </x-alert>
                        </div>
                    @else
                        <x-form.field name="client"
                                      :label="__('deliveries.fields.client')"
                                      :hint="__('deliveries.hints.client')"
                                      required>
                            {{-- Nothing is preselected: which client receives an email is too consequential to default. --}}
                            <x-form.select name="client"
                                           :options="$clients"
                                           :selected="old('client', $selectedClientId)"
                                           :placeholder="__('deliveries.send.choose_client')"
                                           x-bind:disabled="target !== '{{ NotificationTarget::Client->value }}'" />
                        </x-form.field>
                    @endif

                    <x-form.field name="template"
                                  input-id="client_template"
                                  :label="__('deliveries.fields.template')"
                                  :hint="__('deliveries.hints.template')"
                                  required>
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

                {{-- To addresses typed here --}}
                <div x-show="target === '{{ NotificationTarget::Recipients->value }}'" x-cloak class="grid gap-5 sm:grid-cols-2">
                    <x-form.field name="name" :label="__('cadence.fields.name')" :hint="__('cadence.hints.name')" required>
                        <x-form.input name="name"
                                      :value="old('name')"
                                      x-bind:disabled="target !== '{{ NotificationTarget::Recipients->value }}'" />
                    </x-form.field>

                    <x-form.field name="template"
                                  input-id="list_template"
                                  :label="__('deliveries.fields.template')"
                                  :hint="__('deliveries.hints.template')"
                                  required>
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
                        <x-form.input name="subject" :value="old('subject')" x-bind:disabled="! isBlank" />
                    </x-form.field>

                    <x-form.field name="message" :label="__('cadence.fields.message')" :hint="__('cadence.hints.message')" required>
                        <x-form.textarea name="message" rows="6" x-model="message" x-bind:disabled="! isBlank">{{ old('message') }}</x-form.textarea>
                    </x-form.field>
                </div>

                @include('cadence.partials.template-slots')

                @include('cadence.partials.template-attachments')
            </div>

            <x-template-preview-dialog :previews="$templatePreviews" />
        </x-card>

        <x-form.actions>
            @if ($back !== null)
                <x-button :href="$back" variant="secondary" type="button">
                    {{ __('common.actions.cancel') }}
                </x-button>
            @endif

            <x-button type="submit" icon="send">{{ __('deliveries.send.submit') }}</x-button>
        </x-form.actions>
    </form>
</x-app-layout>
