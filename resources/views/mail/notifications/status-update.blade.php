{{-- Written for a recipient list, so it greets the room rather than a named client. --}}
<x-mail.layout :data="$data">
    <p style="margin:0 0 16px 0;">{{ __('mail.notifications.greeting_all') }}</p>

    @foreach (__('mail.notifications.status_update.lines', ['name' => $data->targetName, 'application' => $data->applicationName]) as $line)
        <p style="margin:0 0 16px 0;">{{ $line }}</p>
    @endforeach

    <p style="margin:24px 0 0 0;">{{ __('mail.notifications.closing') }}</p>
    <p style="margin:4px 0 0 0; font-weight:600;">{{ $data->senderName }}</p>
</x-mail.layout>
