{{-- Applied before first paint so the correct theme is never preceded by a flash of the other one. --}}
<script>
    (function () {
        const preference = @json($colorScheme->value);
        const media = window.matchMedia('(prefers-color-scheme: dark)');

        const apply = function () {
            const dark = preference === 'dark' || (preference === 'system' && media.matches);
            document.documentElement.classList.toggle('dark', dark);
        };

        apply();

        if (preference === 'system') {
            media.addEventListener('change', apply);
        }
    })();
</script>
