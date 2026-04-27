import { Link, router, usePage } from '@inertiajs/react';
import axios from 'axios';

const navLinks = [
    { label: 'Dashboard', href: '/dashboard' },
    { label: 'Posts', href: '/posts' },
    { label: 'Labels', href: '/labels' },
    { label: 'Import', href: '/import' },
    { label: 'AI Generate', href: '/ai' },
    { label: 'Notifications', href: '/notifications' },
    { label: 'Billing', href: '/billing' },
    { label: 'Settings', href: '/settings' },
];

export default function AppLayout({ children }) {
    const { auth, blogs = [], selected_blog_id } = usePage().props;
    const user = auth?.user;

    const initials = user?.name
        ? user.name.split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase()
        : 'U';

    const selectedBlogName = blogs.find((b) => b.id === selected_blog_id)?.blog_name ?? 'Select Blog';

    function handleBlogSwitch(e) {
        const blogId = Number(e.target.value);
        if (!blogId) return;
        axios.post('/blogs/switch', { blog_id: blogId }).then(() => router.reload());
    }

    return (
        <div className="flex h-screen bg-gray-50 overflow-hidden">
            {/* Sidebar */}
            <aside className="w-64 flex-shrink-0 bg-white border-r border-gray-100 flex flex-col">
                <div className="h-16 flex items-center gap-3 px-6 border-b border-gray-100">
                    <div className="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                        <svg className="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
                        </svg>
                    </div>
                    <span className="text-base font-bold text-gray-900">BloggerSaaS</span>
                </div>

                <nav className="flex-1 px-3 py-4 flex flex-col gap-0.5 overflow-y-auto">
                    {navLinks.map((link) => (
                        <Link
                            key={link.href}
                            href={link.href}
                            className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors"
                        >
                            {link.label}
                        </Link>
                    ))}
                </nav>

                <div className="p-4 border-t border-gray-100">
                    <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs font-semibold">
                            {initials}
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-gray-900 truncate">{user?.name ?? 'User'}</p>
                            <p className="text-xs text-gray-400 truncate">{user?.email ?? ''}</p>
                        </div>
                    </div>
                </div>
            </aside>

            {/* Main area */}
            <div className="flex-1 flex flex-col overflow-hidden">
                {/* Top bar */}
                <header className="h-16 bg-white border-b border-gray-100 flex items-center justify-between px-6 flex-shrink-0">
                    {/* Blog switcher */}
                    <div>
                        {blogs.length > 0 ? (
                            <select
                                value={selected_blog_id ?? ''}
                                onChange={handleBlogSwitch}
                                className="text-sm text-gray-700 border border-gray-200 rounded-lg px-3 py-1.5 bg-white hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="" disabled>Select Blog</option>
                                {blogs.map((blog) => (
                                    <option key={blog.id} value={blog.id}>{blog.blog_name}</option>
                                ))}
                            </select>
                        ) : (
                            <Link
                                href="/blogs"
                                className="text-sm text-gray-500 border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition-colors"
                            >
                                Connect a blog
                            </Link>
                        )}
                    </div>

                    <div className="flex items-center gap-3">
                        {/* Bell icon */}
                        <button className="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </button>

                        {/* Avatar */}
                        <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs font-semibold">
                            {initials}
                        </div>
                    </div>
                </header>

                {/* Content */}
                <main className="flex-1 overflow-y-auto p-6">
                    {children}
                </main>
            </div>
        </div>
    );
}
