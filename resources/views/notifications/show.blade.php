<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        :root {
            color-scheme: light;
            --blue: #00447c;
            --body: #eef7fc;
            --ink: #17324d;
        }

        body {
            background: var(--body);
            color: var(--ink);
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            margin: 0;
        }

        main {
            margin: 3rem auto;
            max-width: 44rem;
            padding: 0 1rem;
        }

        .panel {
            background: white;
            border: 1px solid #c9e4f4;
            border-top: 4px solid #0093d5;
            border-radius: 8px;
            box-shadow: 0 12px 32px rgb(23 50 77 / 10%);
            padding: 1.5rem;
        }

        h1 {
            font-size: 1.35rem;
            line-height: 1.25;
            margin: 0 0 .75rem;
        }

        .meta {
            color: #5b7286;
            font-size: .875rem;
            margin: 0 0 1.25rem;
        }

        .body {
            font-size: 1rem;
            line-height: 1.6;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        a {
            color: var(--blue);
            display: inline-block;
            font-weight: 600;
            margin-top: 1.5rem;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <main>
        <article class="panel">
            <h1>{{ $title }}</h1>
            <p class="meta">
                {{ __('aho.notifications.read.received_at') }}
                {{ optional($receivedAt)->format('Y-m-d H:i') }}
            </p>

            <div class="body">{{ $body }}</div>

            <a href="{{ $backUrl }}">{{ __('aho.notifications.read.back') }}</a>
        </article>
    </main>
</body>
</html>
