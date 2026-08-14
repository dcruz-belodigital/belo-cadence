@props([
    'sections',
    'navLabel',
])

{{--
    A long page read as a list of sections, with its own contents beside it — the shape
    the user guide and the changelog already use, for pages that are equally a sequence
    of things to scroll through.

    The page is a wide one because the contents list needs a column of its own, so the
    content beside it is held to the narrow measure rather than running the full width.
    Sections carry `scroll-mt-24` so an anchor does not land under the sticky top bar.
--}}
<div class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:items-start">
    @if ($sections !== [])
        <x-section-nav :sections="$sections" :nav-label="$navLabel" />
    @endif

    <div class="min-w-0 max-w-narrow space-y-6">
        {{ $slot }}
    </div>
</div>
