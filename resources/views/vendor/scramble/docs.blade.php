<!doctype html>
<html lang="en" data-theme="{{ $config->get('ui.theme', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="color-scheme" content="{{ $config->get('ui.theme', 'light') }}">
    <title>{{ $config->get('ui.title') ?? config('app.name') . ' - API Docs' }}</title>

    <script src="https://unpkg.com/@stoplight/elements@8.4.2/web-components.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements@8.4.2/styles.min.css">

    <script>
        const originalFetch = window.fetch;

        // Intercept TryIt requests and make sure Sanctum's CSRF token is sent.
        // The session cookie is HttpOnly, so the browser must include it automatically.
        window.fetch = (url, options) => {
            const CSRF_TOKEN_COOKIE_KEY = "XSRF-TOKEN";
            const CSRF_TOKEN_HEADER_KEY = "X-XSRF-TOKEN";
            const PLATFORM_HEADER_KEY = "Platform";
            const PLATFORM_HEADER_VALUE = "web";
            const getCookieValue = (key) => {
                const cookie = document.cookie.split(';').find((cookie) => cookie.trim().startsWith(key));
                return cookie?.split("=")[1];
            };

            const updateFetchHeaders = (
                headers,
                headerKey,
                headerValue,
            ) => {
                if (headers instanceof Headers) {
                    headers.set(headerKey, headerValue);
                } else if (Array.isArray(headers)) {
                    headers.push([headerKey, headerValue]);
                } else if (headers) {
                    headers[headerKey] = headerValue;
                }
            };
            const hasFetchHeader = (headers, headerKey) => {
                if (!headers) {
                    return false;
                }

                const normalizedHeaderKey = headerKey.toLowerCase();

                if (headers instanceof Headers) {
                    return headers.has(headerKey) || headers.has(normalizedHeaderKey);
                }

                if (Array.isArray(headers)) {
                    return headers.some(([key]) => String(key).toLowerCase() === normalizedHeaderKey);
                }

                return Object.keys(headers).some((key) => key.toLowerCase() === normalizedHeaderKey);
            };

            const csrfToken = getCookieValue(CSRF_TOKEN_COOKIE_KEY);
            const { headers = new Headers() } = options || {};

            if (!hasFetchHeader(headers, PLATFORM_HEADER_KEY)) {
                updateFetchHeaders(headers, PLATFORM_HEADER_KEY, PLATFORM_HEADER_VALUE);
            }

            if (csrfToken) {
                updateFetchHeaders(headers, CSRF_TOKEN_HEADER_KEY, decodeURIComponent(csrfToken));
                return originalFetch(url, {
                    ...options,
                    credentials: options?.credentials ?? 'include',
                    headers,
                });
            }

            return originalFetch(url, {
                ...options,
                credentials: options?.credentials ?? 'include',
            });
        };
    </script>

    <style>
        html, body { margin:0; height:100%; }
        body { background-color: var(--color-canvas); }
        /* issues about the dark theme of stoplight/mosaic-code-viewer using web component:
         * https://github.com/stoplightio/elements/issues/2188#issuecomment-1485461965
         */
        [data-theme="dark"] .token.property {
            color: rgb(128, 203, 196) !important;
        }
        [data-theme="dark"] .token.operator {
            color: rgb(255, 123, 114) !important;
        }
        [data-theme="dark"] .token.number {
            color: rgb(247, 140, 108) !important;
        }
        [data-theme="dark"] .token.string {
            color: rgb(165, 214, 255) !important;
        }
        [data-theme="dark"] .token.boolean {
            color: rgb(121, 192, 255) !important;
        }
        [data-theme="dark"] .token.punctuation {
            color: #dbdbdb !important;
        }
        #csrf-refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            padding: 8px 16px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        #csrf-refresh-btn:hover { background: #4338ca; }
    </style>
</head>
<body style="height: 100vh; overflow-y: hidden">
<elements-api
    id="docs"
    tryItCredentialsPolicy="{{ $config->get('ui.try_it_credentials_policy', 'include') }}"
    router="hash"
    @if($config->get('ui.hide_try_it')) hideTryIt="true" @endif
    @if($config->get('ui.hide_schemas')) hideSchemas="true" @endif
    @if($config->get('ui.logo')) logo="{{ $config->get('ui.logo') }}" @endif
    @if($config->get('ui.layout')) layout="{{ $config->get('ui.layout') }}" @endif
/>
<script>
    (async () => {
        const docs = document.getElementById('docs');
        docs.apiDescriptionDocument = @json($spec);
    })();
</script>

@if($config->get('ui.theme', 'light') === 'system')
    <script>
        var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        function updateTheme(e) {
            if (e.matches) {
                window.document.documentElement.setAttribute('data-theme', 'dark');
                window.document.getElementsByName('color-scheme')[0].setAttribute('content', 'dark');
            } else {
                window.document.documentElement.setAttribute('data-theme', 'light');
                window.document.getElementsByName('color-scheme')[0].setAttribute('content', 'light');
            }
        }

        mediaQuery.addEventListener('change', updateTheme);
        updateTheme(mediaQuery);
    </script>
@endif
<button id="csrf-refresh-btn" onclick="refreshAuth()">🔄 Refresh Auth Cookies</button>

<script>
async function refreshAuth() {
    await fetch('/sanctum/csrf-cookie', { credentials: 'include' });

    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }

    function getAssociatedInput(label) {
        return label.control
            ?? label.querySelector('input')
            ?? label.nextElementSibling?.querySelector('input');
    }

    const xsrf = getCookie('XSRF-TOKEN');

    document.querySelectorAll('label').forEach(label => {
        const text = label.textContent.trim();
        const input = getAssociatedInput(label);
        if (!input) return;

        if (text === 'X-XSRF-TOKEN') {
            input.value = xsrf ?? '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    const btn = document.getElementById('csrf-refresh-btn');
    btn.textContent = '✅ Cookies Injected';
    setTimeout(() => btn.textContent = '🔄 Refresh Auth Cookies', 2000);
}

(async function () {
    // Prime the CSRF cookie once, then keep retrying until Elements renders.
    await fetch('/sanctum/csrf-cookie', {
        credentials: 'include'
    });

    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }

    function getAssociatedInput(label) {
        return label.control
            ?? label.querySelector('input')
            ?? label.nextElementSibling?.querySelector('input');
    }

    function injectAuth() {
        const xsrf = getCookie('XSRF-TOKEN');
        if (!xsrf) return;

        const labels = document.querySelectorAll('label');

        labels.forEach(label => {
            const text = label.textContent.trim();
            const input = getAssociatedInput(label);

            if (!input) return;

            if (text === 'X-XSRF-TOKEN') {
                input.value = xsrf;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    let attempts = 0;
    const interval = setInterval(() => {
        injectAuth();
        attempts++;
        if (attempts > 30) clearInterval(interval);
    }, 500);
})();
</script>
</body>
</html>
