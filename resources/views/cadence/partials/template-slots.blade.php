@php
    use App\Enums\NotificationTarget;
    use App\Support\TemplateSlots;
    use App\ValueObjects\TemplateBinding;

    /*
    | The blanks the chosen template leaves, and what fills each of them.
    |
    | The template can be changed without leaving the page, so the browser is handed the
    | slots of every template rather than only the one currently chosen. A blank template
    | is the exception: its blanks are the `{{ placeholder }}` tokens being typed into the
    | message right now, so they are read from it as they are typed. The server reads the
    | submitted message again, so what the browser found is never the authority.
    |
    | A schedule with no client has nobody whose answers could fill a blank, so only a
    | literal is offered — the same `target` the surrounding form already tracks.
    */
    $slotOptions ??= TemplateSlots::formOptions(collect());

    /** @var \App\Models\NotificationSchedule|null $schedule */
    $schedule ??= null;

    /*
    | What each slot is already bound to, so a saved schedule reopens with its own choices
    | selected. Old input wins, because a form coming back from a failure has to show what
    | was submitted rather than what is stored.
    */
    $stored = collect($schedule?->template_bindings?->bindings ?? [])
        ->map(fn (TemplateBinding $binding): array => [
            'source' => $binding->token(),
            'value' => (string) ($binding->value ?? ''),
        ]);

    $currentBindings = collect(old('template_bindings', $stored))
        ->map(fn (mixed $binding): array => [
            'source' => (string) (is_array($binding) ? ($binding['source'] ?? '') : ''),
            'value' => (string) (is_array($binding) ? ($binding['value'] ?? '') : ''),
        ])
        ->all();
@endphp

<div class="space-y-4"
     x-data="{
        slotsByTemplate: {{ Js::from($slotOptions['by_template']) }},
        anyChoices: {{ Js::from($slotOptions['any']) }},
        bindings: {{ Js::from($currentBindings) }},
        get slots() {
            if (this.isBlank) {
                // The same shape as TemplateSlots::PLACEHOLDER, which is what actually decides.
                const found = [...(this.message ?? '').matchAll(/\{\{\s*([a-z][a-z0-9_]*)\s*\}\}/g)]
                    .map((match) => match[1]);

                return [...new Set(found)].map((key) => ({ key, label: key, choices: this.anyChoices }));
            }

            return this.slotsByTemplate[this.template] ?? [];
        },
        choicesFor(slot) {
            return this.target === '{{ NotificationTarget::Client->value }}' ? slot.choices : {};
        },
        binding(key) {
            if (! this.bindings[key]) {
                this.bindings[key] = { source: '', value: '' };
            }

            return this.bindings[key];
        },
     }"
     x-show="slots.length > 0"
     x-cloak>

    <div>
        <x-form.label>{{ __('cadence.slots.title') }}</x-form.label>
        <p class="mt-1 text-meta text-foreground-subtle">{{ __('cadence.slots.description') }}</p>
    </div>

    {{-- The rows are drawn by the browser, so their messages have nowhere of their own. --}}
    <x-form.error :name="['template_bindings', 'template_bindings.*']" />

    <template x-for="slot in slots" x-bind:key="slot.key">
        <div class="grid gap-3 border-b border-border pb-4 last:border-0 last:pb-0 sm:grid-cols-2 sm:items-end">
            <label class="block space-y-1.5">
                <span class="block text-label" x-text="slot.label"></span>
                <select class="form-control"
                        x-bind:name="'template_bindings[' + slot.key + '][source]'"
                        x-model="binding(slot.key).source">
                    <option value="">{{ __('common.placeholders.none') }}</option>
                    <option value="{{ TemplateBinding::MANUAL }}">{{ __('cadence.slots.manual') }}</option>
                    <template x-for="(label, token) in choicesFor(slot)" x-bind:key="token">
                        <option x-bind:value="token" x-text="label"></option>
                    </template>
                </select>
            </label>

            <label class="block space-y-1.5" x-show="binding(slot.key).source === '{{ TemplateBinding::MANUAL }}'" x-cloak>
                <span class="block text-label">{{ __('cadence.slots.value') }}</span>
                <input type="text" class="form-control" maxlength="255"
                       x-bind:name="'template_bindings[' + slot.key + '][value]'"
                       x-model="binding(slot.key).value">
            </label>
        </div>
    </template>
</div>
