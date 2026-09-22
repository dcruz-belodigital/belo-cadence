@php
    use App\Enums\NotificationTarget;
    use App\Support\TemplateSlots;
    use App\ValueObjects\TemplateBinding;

    /*
    | The client files this notification carries.
    |
    | Unlike a template value, an attachment fills no blank in any wording, so it is not
    | declared by the template and does not change when the template does: any notification
    | about a client may carry any of that client's files.
    |
    | A notification addressed to a list of typed addresses has no client whose files could
    | be read, so this disappears entirely — the same `target` the surrounding form tracks.
    | Every entry is one of the same attribute-and-path tokens a template value is bound
    | with, so choosing a file field inside repeating rows attaches one file per row.
    */
    $slotOptions ??= TemplateSlots::formOptions(collect());

    /** @var \App\Models\NotificationSchedule|null $schedule */
    $schedule ??= null;

    $choices = $slotOptions['attachments'] ?? [];

    // Old input wins, because a form coming back from a failure shows what was submitted.
    $chosen = collect(old('attachments', $schedule?->attachment_bindings?->tokens() ?? []))
        ->filter(fn (mixed $token): bool => is_string($token))
        ->values()
        ->all();
@endphp

@if ($choices !== [])
    <div class="space-y-3" x-show="target === '{{ NotificationTarget::Client->value }}'" x-cloak>
        <div>
            <x-form.label>{{ __('cadence.attachments.title') }}</x-form.label>
            <p class="mt-1 text-meta text-foreground-subtle">{{ __('cadence.attachments.description') }}</p>
        </div>

        {{-- The message belongs to the set of choices rather than to any one of them. --}}
        <x-form.error :name="['attachments', 'attachments.*']" />

        <div class="space-y-2">
            @foreach ($choices as $token => $label)
                <label class="flex items-start gap-2.5 text-body">
                    <input type="checkbox"
                           name="attachments[]"
                           value="{{ $token }}"
                           @checked(in_array($token, $chosen, true))
                           class="focus-ring mt-0.5 size-4 shrink-0 rounded border-border-strong bg-surface accent-primary">
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>
@endif
