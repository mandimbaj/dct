@if (auth()->check())
    <script>
        (() => {
            if (window.__ahoUserPageVisitTrackerReady) return;
            window.__ahoUserPageVisitTrackerReady = true;

            const endpoint = @json(route('admin.user-history.record'));
            const csrfToken = @json(csrf_token());
            let lastTrackedPath = '';

            const shouldTrack = () => {
                const path = window.location.pathname;

                return path.startsWith('/admin/')
                    && ! /\/(login|logout|password)(\/|$)/i.test(path);
            };

            const track = () => {
                const path = window.location.pathname + window.location.search;

                if (! shouldTrack() || path === lastTrackedPath) return;

                lastTrackedPath = path;

                window.fetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ path }),
                }).catch(() => {});
            };

            document.addEventListener('livewire:navigated', () => window.setTimeout(track, 75));
        })();
    </script>
@endif
