@props([
    'sections',
    'navLabel',
])

{{--
    A page's own sections beside it. The list sticks to the top on wide screens and sits
    above the content on narrow ones.

    `$sections` is a list of ['id' => …, 'label' => …]; each id must match the id of the
    thing it points at, so the anchors carry the reader to it.
--}}
<nav class="rounded-card border border-border bg-surface p-3 shadow-card lg:sticky lg:top-24"
     aria-label="{{ $navLabel }}">
    <p class="px-2 pb-2 text-overline uppercase text-foreground-subtle">{{ $navLabel }}</p>

    <ul class="space-y-0.5">
        @foreach ($sections as $section)
            <li>
                <a href="#{{ $section['id'] }}"
                   class="focus-ring block truncate rounded-control px-2 py-1.5 text-body text-foreground-muted transition hover:bg-surface-muted hover:text-foreground">
                    {{ $section['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
