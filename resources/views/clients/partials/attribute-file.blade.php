@php
    /*
    | One uploaded file, wherever an answer holds one.
    |
    | The link is the only way to the bytes: they live on the private disk, and the route
    | asks the client policy before it answers. Somebody who may read the page may read
    | the file, which is the one rule, stated once.
    |
    | `$file` is a `StoredFile` read off the answer, or null when the answer has been
    | edited into something that is not one.
    */
@endphp

@if ($file !== null)
    <a href="{{ route('clients.files.show', $file->id) }}"
       class="focus-ring inline-flex max-w-full items-center gap-2 rounded-control text-body text-primary underline decoration-border-strong underline-offset-4">
        <x-icon name="document" size="size-4" class="shrink-0" />
        <span class="truncate">{{ $file->name }}</span>
    </a>
@endif
