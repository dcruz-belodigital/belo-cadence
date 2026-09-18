@php
    use App\Enums\NotificationTarget;
@endphp

{{--
    The "sends to" choice, shared by the schedule form and the send-now form so the two
    always offer the same wording. The labels come from NotificationTarget, like every
    other state the application names.

    The radio itself is visually hidden but still focusable; the label around it is what
    is drawn, and Alpine's `target` decides which one reads as chosen.
--}}
<div class="flex flex-wrap gap-2">
    @foreach (NotificationTarget::cases() as $case)
        <label class="focus-ring flex cursor-pointer items-center gap-2 rounded-control border border-border px-3 py-2 text-body transition hover:bg-surface-sunken"
               x-bind:class="target === '{{ $case->value }}' ? 'border-primary bg-primary-soft/40' : ''">
            <input type="radio" name="target" value="{{ $case->value }}" x-model="target" class="sr-only">
            {{ $case->label() }}
        </label>
    @endforeach
</div>
