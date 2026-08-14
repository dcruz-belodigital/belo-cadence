<x-mail.layout :data="$data">
    <p style="margin:0 0 16px 0;">{{ __('mail.client_notifications.greeting', ['name' => $data->clientName]) }}</p>

    @foreach (__('mail.client_notifications.general_reminder.lines', ['application' => $data->applicationName]) as $line)
        <p style="margin:0 0 16px 0;">{{ $line }}</p>
    @endforeach

    <p style="margin:24px 0 0 0;">{{ __('mail.client_notifications.closing') }}</p>
    <p style="margin:4px 0 0 0; font-weight:600;">{{ $data->senderName }}</p>
</x-mail.layout>
