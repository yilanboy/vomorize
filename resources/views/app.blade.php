<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <script>
            /*
             * A guest's learning record lives in local storage, so the server renders their group
             * tiles from the only reading it has: every group untouched, which the level page
             * paints as actionable. Whichever of those groups is really mid-schedule is then
             * demoted the moment the page hydrates, and the grid visibly flashes.
             *
             * Nothing here can supply the real statuses before paint. What it can do is name the
             * groups the server's reading does not cover — exactly those local storage holds a
             * record for — and hold their tiles back until the page resolves them. Every other
             * group is genuinely untouched, so the server already had it right and its tile is
             * left to paint immediately.
             */
            (() => {
                if (!/^\/levels\//.test(location.pathname)) {
                    return;
                }

                let ids = [];

                try {
                    const stored = localStorage.getItem('vomorize_guest_progress');

                    ids = stored
                        ? Object.keys(JSON.parse(stored)).filter((id) => /^\d+$/.test(id))
                        : [];
                } catch {
                    // Storage may be unavailable in restricted browser contexts.
                }

                if (ids.length === 0) {
                    return;
                }

                const style = document.createElement('style');

                // The held-back tile wears the quiet tier: a hairline, and no surface of its own.
                style.textContent =
                    ids.map((id) => `.tile-unverified[data-group-id="${id}"]`).join(',')
                    + '{border-width:1px;border-color:var(--tile-border);background-color:transparent}'
                    + '.summary-unverified{visibility:hidden}';

                document.head.append(style);
            })();
        </script>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans text-base antialiased">
        <x-inertia::app />
    </body>
</html>
