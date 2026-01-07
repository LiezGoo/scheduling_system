/**
 * Notification System - Real-Time Updates
 *
 * Handles real-time notifications using Laravel Echo and broadcasting
 * Updates notification UI without page refresh
 */

class NotificationSystem {
    constructor() {
        this.notificationBadge = document.getElementById('notificationBadge');
        this.notificationList = document.getElementById('notificationList');
        this.notificationBellIcon = document.getElementById('notificationBellIcon');
        this.markAllReadBtn = document.getElementById('markAllReadBtn');
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        this.init();
    }

    /**
     * Initialize the notification system
     */
    init() {
        this.loadNotifications();
        this.setupEventListeners();
        this.setupBroadcasting();
    }

    /**
     * Load initial notifications from server
     */
    async loadNotifications() {
        try {
            const response = await fetch('/notifications/unread', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });

            if (!response.ok) throw new Error('Failed to load notifications');

            const data = await response.json();
            this.updateNotificationUI(data.notifications, data.unread_count);
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    /**
     * Update the notification UI with new data
     */
    updateNotificationUI(notifications, unreadCount) {
        // Update badge
        this.updateBadge(unreadCount);

        // Update notification list
        if (notifications.length === 0) {
            this.notificationList.innerHTML = `
                <li class="px-3 py-4 text-center text-muted">
                    <i class="fa-regular fa-bell-slash mb-2" style="font-size: 2rem; opacity: 0.5;"></i>
                    <p class="mb-0 small">No notifications</p>
                </li>
            `;
        } else {
            this.notificationList.innerHTML = notifications.map(notification =>
                this.createNotificationItem(notification)
            ).join('');
        }
    }

    /**
     * Create HTML for a single notification item
     */
    createNotificationItem(notification) {
        const data = notification.data;
        const isUnread = !notification.read_at;
        const relativeTime = this.getRelativeTime(notification.created_at);
        const typeClass = `notification-badge-${data.type || 'info'}`;
        const url = data.related_url || '#';

        return `
            <li>
                <a href="${url}"
                   class="notification-item ${isUnread ? 'unread' : ''}"
                   data-notification-id="${notification.id}">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="notification-title">${this.escapeHtml(data.title)}</span>
                        <span class="badge ${typeClass} ms-2" style="font-size: 0.7rem;">
                            ${data.type || 'info'}
                        </span>
                    </div>
                    <p class="notification-message mb-1">${this.escapeHtml(data.message)}</p>
                    <small class="notification-time">
                        <i class="fa-regular fa-clock me-1"></i>${relativeTime}
                    </small>
                </a>
            </li>
        `;
    }

    /**
     * Update the notification badge
     */
    updateBadge(count) {
        if (count > 0) {
            this.notificationBadge.textContent = count > 99 ? '99+' : count;
            this.notificationBadge.style.display = 'inline-block';
        } else {
            this.notificationBadge.style.display = 'none';
        }
    }

    /**
     * Animate the notification bell
     */
    animateBell() {
        this.notificationBellIcon.classList.add('notification-bell-animate');
        setTimeout(() => {
            this.notificationBellIcon.classList.remove('notification-bell-animate');
        }, 500);
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Mark all as read
        this.markAllReadBtn?.addEventListener('click', async (e) => {
            e.preventDefault();
            await this.markAllAsRead();
        });

        // Mark individual notification as read on click
        this.notificationList?.addEventListener('click', async (e) => {
            const notificationItem = e.target.closest('.notification-item');
            if (notificationItem && notificationItem.classList.contains('unread')) {
                const notificationId = notificationItem.dataset.notificationId;
                await this.markAsRead(notificationId);
            }
        });
    }

    /**
     * Setup Laravel Echo for real-time broadcasting
     */
    setupBroadcasting() {
        if (typeof Echo === 'undefined') {
            console.warn('Laravel Echo not loaded. Real-time notifications disabled.');
            return;
        }

        const userId = document.querySelector('meta[name="user-id"]')?.content;
        if (!userId) {
            console.warn('User ID not found. Cannot setup broadcasting.');
            return;
        }

        // Listen to private channel for user-specific notifications
        Echo.private(`App.Models.User.${userId}`)
            .notification((notification) => {
                console.log('New notification received:', notification);
                this.handleNewNotification(notification);
            });
    }

    /**
     * Handle new notification from broadcast
     */
    handleNewNotification(notification) {
        // Animate bell
        this.animateBell();

        // Reload notifications
        this.loadNotifications();

        // Optional: Show browser notification
        this.showBrowserNotification(notification);
    }

    /**
     * Show browser notification (requires permission)
     */
    showBrowserNotification(notification) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(notification.title, {
                body: notification.message,
                icon: '/images/logo.png',
                badge: '/images/logo.png'
            });
        }
    }

    /**
     * Mark a notification as read
     */
    async markAsRead(notificationId) {
        try {
            const response = await fetch(`/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });

            if (!response.ok) throw new Error('Failed to mark notification as read');

            const data = await response.json();
            this.updateBadge(data.unread_count);

            // Update the notification item visually
            const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationItem) {
                notificationItem.classList.remove('unread');
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    /**
     * Mark all notifications as read
     */
    async markAllAsRead() {
        try {
            const response = await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });

            if (!response.ok) throw new Error('Failed to mark all notifications as read');

            const data = await response.json();
            this.updateBadge(data.unread_count);

            // Update all notification items visually
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }

    /**
     * Get relative time string
     */
    getRelativeTime(timestamp) {
        const now = new Date();
        const notificationDate = new Date(timestamp);
        const diffMs = now - notificationDate;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;
        if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
        if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;

        return notificationDate.toLocaleDateString();
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Request browser notification permission
     */
    static requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
}

// Initialize notification system when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const notificationSystem = new NotificationSystem();

    // Request browser notification permission
    NotificationSystem.requestNotificationPermission();
});
