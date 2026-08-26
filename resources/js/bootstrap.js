// MeetRoom - Bootstrap
import axios from 'axios';

// Configure axios
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// Get CSRF token
const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Handle axios errors globally
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            // CSRF token mismatch - reload page
            window.location.reload();
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
