import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Without Reverb settings (VITE_REVERB_* in .env at build time) chat still works, just without
// live updates. Never let a missing key throw here: that would stop the rest of app.js.
if (import.meta.env.VITE_REVERB_APP_KEY) {
    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                },
            },
        });

        // Signal that Echo is ready
        window.EchoReady = true;
        document.dispatchEvent(new Event('echo-ready'));
    } catch (error) {
        console.warn('Real-time chat is unavailable:', error);
    }
} else {
    console.warn('Real-time chat is off: set the REVERB_* values in .env and run `npm run build`.');
}
