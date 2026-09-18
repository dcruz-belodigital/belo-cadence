{{--
    The tab icon. The SVG is the real one: it carries a neutral light/dark ink of its
    own, which `resources/js/favicon.js` then replaces with the current theme's primary.
    The PNG is there for browsers that do not take an SVG favicon, and the touch icon
    for a home screen, where a transparent mark would be backed with black.
--}}
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="icon" href="{{ asset('favicon-32.png') }}" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
