@props(['size' => 'size-5'])

{{--
    The application's mark: a paper plane, folded rather than flat.

    The dart is drawn as two facets sharing the crease from the nose down to the notch.
    Painting the far facet at part opacity is what gives the fold — and because both
    facets are `currentColor`, the mark takes the colour of whatever it is set in, so it
    follows the theme without a second definition. The corners are rounded by stroking
    each facet in its own fill rather than by rounding the path by hand.

    Kept in step with `public/favicon.svg`, which is the same geometry in a fixed,
    neutral ink for the browser tab.
--}}
<svg {{ $attributes->merge(['class' => $size.' shrink-0']) }}
     viewBox="0 0 24 24"
     fill="none"
     aria-hidden="true"
     focusable="false">
    {{-- Motion, trailing the plane rather than crossing it. --}}
    <g stroke="currentColor" stroke-width="1.9" stroke-linecap="round" opacity="0.45">
        <path d="M2.1 15.5h3" />
        <path d="M4.7 19.2h3.8" />
    </g>

    {{-- The near facet, catching the light. --}}
    <path d="M21.4 2.6 4.2 9.4l6.8 3.2z"
          fill="currentColor"
          stroke="currentColor"
          stroke-width="1.5"
          stroke-linejoin="round" />

    {{-- The far facet, folded away from it. --}}
    <path d="M21.4 2.6 11 12.6l3.2 6.8z"
          fill="currentColor"
          stroke="currentColor"
          stroke-width="1.5"
          stroke-linejoin="round"
          opacity="0.55" />
</svg>
