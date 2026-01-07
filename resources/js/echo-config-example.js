/**
 * Laravel Echo Configuration for Real-Time Notifications
 *
 * IMPORTANT: This file shows the configuration needed for Laravel Echo.
 * Copy the appropriate section to your resources/js/bootstrap.js file.
 */

// ============================================
// OPTION 1: Using Pusher
// ============================================
/*
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
*/

// Steps for Pusher:
// 1. Install: npm install laravel-echo pusher-js
// 2. Add to .env:
//    BROADCAST_CONNECTION=pusher
//    PUSHER_APP_ID=your-app-id
//    PUSHER_APP_KEY=your-app-key
//    PUSHER_APP_SECRET=your-app-secret
//    PUSHER_APP_CLUSTER=your-cluster
//    VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
//    VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
// 3. Install: composer require pusher/pusher-php-server
// 4. Build: npm run build


// ============================================
// OPTION 2: Using Laravel Reverb (Laravel 11+)
// ============================================
/*
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
*/

// Steps for Reverb:
// 1. Install: composer require laravel/reverb
// 2. Install: php artisan reverb:install
// 3. Install: npm install laravel-echo pusher-js
// 4. Configure .env:
//    BROADCAST_CONNECTION=reverb
//    REVERB_APP_ID=your-app-id
//    REVERB_APP_KEY=your-app-key
//    REVERB_APP_SECRET=your-app-secret
//    REVERB_HOST="localhost"
//    REVERB_PORT=8080
//    REVERB_SCHEME=http
// 5. Build: npm run build
// 6. Start Reverb: php artisan reverb:start


// ============================================
// TESTING WITHOUT BROADCASTING (Development)
// ============================================
// If you want to test the UI without real-time features:
// - Leave Echo unconfigured
// - Notifications will still work via database
// - Just won't update in real-time
// - Refresh page to see new notifications


// ============================================
// VERIFYING ECHO IS LOADED
// ============================================
// After configuration, test in browser console:
// console.log(window.Echo);
// Should output the Echo instance, not undefined
