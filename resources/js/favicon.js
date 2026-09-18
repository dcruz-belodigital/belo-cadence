/*
| The tab icon, inked in the colour the page itself is drawn in.
|
| Only the colour changes. The mark stays in public/favicon.svg and is not repeated
| here: it is fetched once and given one extra `color` declaration, which recolours all
| of it because every path in it inks from `currentColor`. The value is read off the
| live document rather than listed here, so a theme added to themes.css is picked up
| with no change to this file — the same rule the rest of the theming follows.
|
| A theme's light and dark palettes carry different primaries and that switch happens
| without a reload, so the icon is repainted whenever the root element changes.
*/

const link = document.querySelector('link[rel="icon"][type="image/svg+xml"]');

if (link !== null) {
    // Read before the href becomes a data URI, and only once however often we repaint.
    const mark = fetch(link.href).then((response) => response.text());

    const paint = () => {
        const ink = getComputedStyle(document.documentElement).getPropertyValue('--cadence-primary').trim();

        if (ink === '') {
            // In development the stylesheet is injected by a module of its own, which can land after this one.
            window.addEventListener('load', paint, { once: true });

            return;
        }

        mark.then((svg) => {
            // Last declaration wins, so this outranks both the neutral and its dark variant.
            const inked = svg.replace('</svg>', `<style>svg{color:${ink}}</style></svg>`);

            link.setAttribute('href', `data:image/svg+xml,${encodeURIComponent(inked)}`);
        });
    };

    paint();

    new MutationObserver(paint).observe(document.documentElement, {
        attributeFilter: ['class', 'data-theme'],
    });
}
