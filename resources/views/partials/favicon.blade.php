{{--
    The tab icon. The SVG is the real one and carries its own light/dark handling; the
    PNG is there for browsers that do not take an SVG favicon, and the touch icon for a
    home screen, where a transparent mark would be backed with black.
--}}
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="icon" href="{{ asset('favicon-32.png') }}" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
