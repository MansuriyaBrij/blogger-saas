import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { useNotifications } from '../hooks/useNotifications';

export default function NotificationBell({ userId }) {
    const { notifications, unreadCount, loading, markRead, markAllRead, remove } = useNotifications(userId);
    const [open, setOpen] = useState(false);
    const dropdownRef = useRef(null);

    // Close dropdown on outside click
    useEffect(() => {
        function handleClickOutside(e) {
            if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
                setOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    function handleNotificationClick(n) {
        markRead(n.id);
        setOpen(false);
        if (n.data?.url || n.url) {
            router.visit(n.data?.url ?? n.url);
        }
    }

    const recentItems = notifications.slice(0, 10);

    return (
        <div className="relative" ref={dropdownRef}>
            {/* Bell button */}
            <button
                onClick={() => setOpen((v) => !v)}
                className="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
                aria-label="Notifications"
            >
                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                    />
                </svg>

                {unreadCount > 0 && (
                    <span className="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white leading-none">
                        {unreadCount > 99 ? '99+' : unreadCount}
                    </span>
                )}
            </button>

            {/* Dropdown */}
            {open && (
                <div className="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden">
                    {/* Header */}
                    <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                        <span className="text-sm font-semibold text-gray-800">Notifications</span>
                        {unreadCount > 0 && (
                            <button
                                onClick={markAllRead}
                                className="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                            >
                                Mark all read
                            </button>
                        )}
                    </div>

                    {/* List */}
                    <div className="max-h-96 overflow-y-auto divide-y divide-gray-50">
                        {loading ? (
                            <div className="py-8 text-center text-sm text-gray-400">Loading…</div>
                        ) : recentItems.length === 0 ? (
                            <div className="py-8 text-center text-sm text-gray-400">No notifications yet</div>
                        ) : (
                            recentItems.map((n) => {
                                const payload = n.data ?? {};
                                const title = payload.title ?? n.type;
                                const body  = payload.body  ?? '';
                                const unread = !n.read_at;

                                return (
                                    <div
                                        key={n.id}
                                        className={`flex items-start gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors ${unread ? 'bg-indigo-50/40' : ''}`}
                                        onClick={() => handleNotificationClick({ ...n, url: payload.url })}
                                    >
                                        {/* Unread dot */}
                                        <div className="mt-1.5 flex-shrink-0">
                                            {unread
                                                ? <span className="block w-2 h-2 rounded-full bg-indigo-500" />
                                                : <span className="block w-2 h-2" />
                                            }
                                        </div>

                                        <div className="flex-1 min-w-0">
                                            <p className={`text-sm ${unread ? 'font-semibold text-gray-900' : 'font-medium text-gray-700'} truncate`}>
                                                {title}
                                            </p>
                                            {body && (
                                                <p className="text-xs text-gray-500 mt-0.5 line-clamp-2">{body}</p>
                                            )}
                                        </div>

                                        <button
                                            onClick={(e) => { e.stopPropagation(); remove(n.id); }}
                                            className="flex-shrink-0 text-gray-300 hover:text-gray-500 mt-0.5"
                                            aria-label="Dismiss"
                                        >
                                            <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                );
                            })
                        )}
                    </div>

                    {/* Footer */}
                    {notifications.length > 10 && (
                        <div className="px-4 py-3 border-t border-gray-100 text-center">
                            <button
                                onClick={() => { setOpen(false); router.visit('/notifications'); }}
                                className="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                            >
                                View all notifications
                            </button>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
