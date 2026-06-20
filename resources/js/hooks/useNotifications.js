import { useCallback, useEffect, useRef, useState } from 'react';
import axios from 'axios';

/**
 * Manages in-app notifications via DB poll on mount + WebSocket push.
 *
 * Requires laravel-echo + pusher-js installed and Echo initialised on window.Echo.
 * Falls back to DB-only (no real-time) when window.Echo is unavailable.
 */
export function useNotifications(userId) {
    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [loading, setLoading] = useState(true);
    const channelRef = useRef(null);

    // Load initial notifications from DB
    useEffect(() => {
        axios.get('/notifications')
            .then(({ data }) => {
                const items = data.data ?? [];
                setNotifications(items);
                setUnreadCount(items.filter((n) => !n.read_at).length);
            })
            .finally(() => setLoading(false));
    }, []);

    // Subscribe to private WebSocket channel
    useEffect(() => {
        if (!userId || !window.Echo) return;

        channelRef.current = window.Echo
            .private(`private-user.${userId}`)
            .notification((notification) => {
                setNotifications((prev) => [notification, ...prev]);
                setUnreadCount((c) => c + 1);
            });

        return () => {
            if (channelRef.current) {
                window.Echo.leave(`private-user.${userId}`);
                channelRef.current = null;
            }
        };
    }, [userId]);

    const markRead = useCallback((id) => {
        // Optimistic update
        setNotifications((prev) =>
            prev.map((n) => (n.id === id ? { ...n, read_at: new Date().toISOString() } : n))
        );
        setUnreadCount((c) => Math.max(0, c - 1));

        axios.post(`/notifications/${id}/read`).catch(() => {
            // Rollback on failure
            setNotifications((prev) =>
                prev.map((n) => (n.id === id ? { ...n, read_at: null } : n))
            );
            setUnreadCount((c) => c + 1);
        });
    }, []);

    const markAllRead = useCallback(() => {
        setNotifications((prev) => prev.map((n) => ({ ...n, read_at: n.read_at ?? new Date().toISOString() })));
        setUnreadCount(0);

        axios.post('/notifications/read-all').catch(() => {
            // Re-fetch on failure
            axios.get('/notifications').then(({ data }) => {
                const items = data.data ?? [];
                setNotifications(items);
                setUnreadCount(items.filter((n) => !n.read_at).length);
            });
        });
    }, []);

    const remove = useCallback((id) => {
        setNotifications((prev) => prev.filter((n) => n.id !== id));
        setUnreadCount((c) => Math.max(0, c - 1));
        axios.delete(`/notifications/${id}`);
    }, []);

    return { notifications, unreadCount, loading, markRead, markAllRead, remove };
}
