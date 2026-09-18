<x-mail.layout :data="$data">
    <p style="margin:0 0 16px 0;">{{ __('mail.notifications.greeting', ['name' => $data->targetName]) }}</p>

    @foreach (__('mail.notifications.annual_reminder.lines', ['application' => $data->applicationName]) as $line)
        <p style="margin:0 0 16px 0;">{{ $line }}</p>
    @endforeach

    {{--
        The blanks this template leaves, filled in from the client's own attributes or
        written on the schedule. A blank nobody bound simply does not appear; one that was
        bound and could not be filled never reaches here, because the send fails first.

        `pre-line` is what lets a value read out of repeating rows print one row per line.
    --}}
    @if ($data->slot('due_date') !== '')
        <p style="margin:0 0 16px 0;">
            {{ __('mail.notifications.annual_reminder.due', ['date' => $data->slot('due_date')]) }}
        </p>
    @endif

    @if ($data->slot('contacts') !== '')
        <p style="margin:0 0 4px 0;">{{ __('mail.notifications.annual_reminder.contacts') }}</p>
        <p style="margin:0 0 16px 0; white-space:pre-line;">{{ $data->slot('contacts') }}</p>
    @endif

    <p style="margin:24px 0 0 0;">{{ __('mail.notifications.closing') }}</p>
    <p style="margin:4px 0 0 0; font-weight:600;">{{ $data->senderName }}</p>
</x-mail.layout>
