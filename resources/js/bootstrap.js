// MeetRoom - Bootstrap
import axios from 'axios';

// Configure axios
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

function setCsrfFromMeta() {
    const el = document.head.querySelector('meta[name="csrf-token"]');
    if (el && el.content) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = el.content;
        return true;
    }
    return false;
}

// Set CSRF token immediately
setCsrfFromMeta();

// Handle axios errors globally
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            console.warn('[app] 419 — fetching fresh CSRF token');

            // Fetch a fresh CSRF token from the server, then retry the failed request once
            return window.axios.get('/csrf-token')
                .then(({ data }) => {
                    if (data && data.token) {
                        // Update meta tag AND axios header
                        const meta = document.head.querySelector('meta[name="csrf-token"]');
                        if (meta) meta.content = data.token;
                        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = data.token;
                    }
                    // Retry original request once
                    const config = error.config;
                    if (config && !config._csrfRetried) {
                        config._csrfRetried = true;
                        config.headers['X-CSRF-TOKEN'] = window.axios.defaults.headers.common['X-CSRF-TOKEN'];
                        return window.axios(config);
                    }
                    return Promise.reject(error);
                })
                .catch(() => {
                    // CSRF refresh itself failed — reload only as absolute last resort
                    console.error('[app] CSRF refresh failed');
                    return Promise.reject(error);
                });
        }

        return Promise.reject(error);
    }
);

/**
 * Lazily create a Laravel Echo instance pointed at the Reverb server.
 * Only called from the meeting room page with explicit connection config.
 */
window.initEcho = async (config) => {
    const { default: Echo } = await import('laravel-echo');
    const { default: Pusher } = await import('pusher-js');

    window.Pusher = Pusher;

    return new Echo({
        broadcaster: 'reverb',
        key: config.key,
        wsHost: config.wsHost,
        wsPort: config.wsPort || 80,
        wssPort: config.wssPort || 443,
        forceTLS: config.forceTLS ?? (window.location.protocol === 'https:'),
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
    });
};
