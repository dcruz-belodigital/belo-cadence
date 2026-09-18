{{--
    The one template with no wording of its own: it prints the message written on the
    schedule, as plain text split into the paragraphs it was typed as. Blade escapes it,
    so nothing a person types can introduce markup into the email.
--}}
<x-mail.layout :data="$data">
    @forelse ($data->messageParagraphs() as $paragraph)
        <p style="margin:0 0 16px 0; white-space:pre-line;">{{ $paragraph }}</p>
    @empty
        <p style="margin:0 0 16px 0;">{{ __('mail.notifications.blank.empty') }}</p>
    @endforelse
</x-mail.layout>
