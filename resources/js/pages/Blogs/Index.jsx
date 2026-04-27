import AppLayout from '@/Layouts/AppLayout';
import { useForm, usePage } from '@inertiajs/react';

function formatDate(dateStr) {
    if (!dateStr) return 'Never';
    return new Date(dateStr).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

export default function BlogsIndex({ blogs }) {
    const { flash } = usePage().props;
    const { post, processing } = useForm();

    function handleConnect() {
        post('/blogs/connect');
    }

    return (
        <AppLayout>
            <div className="max-w-4xl mx-auto">
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-xl font-semibold text-gray-900">Your Blogs</h1>
                        <p className="text-sm text-gray-500 mt-0.5">Connect and manage your Blogger accounts</p>
                    </div>
                    <button
                        onClick={handleConnect}
                        disabled={processing}
                        className="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-60 transition-colors"
                    >
                        {processing ? 'Connecting…' : 'Connect Blogs'}
                    </button>
                </div>

                {/* Flash message */}
                {flash?.success && (
                    <div className="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
                        {flash.success}
                    </div>
                )}

                {/* Blog grid */}
                {blogs.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-24 text-center">
                        <div className="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <svg className="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
                            </svg>
                        </div>
                        <h3 className="text-sm font-medium text-gray-900 mb-1">No blogs connected</h3>
                        <p className="text-sm text-gray-500 mb-4">Connect your Google Blogger account to get started.</p>
                        <button
                            onClick={handleConnect}
                            disabled={processing}
                            className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-60 transition-colors"
                        >
                            {processing ? 'Connecting…' : 'Connect Blogs'}
                        </button>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {blogs.map((blog) => (
                            <div key={blog.id} className="bg-white rounded-xl border border-gray-100 p-5 flex flex-col gap-3">
                                <div>
                                    <h2 className="text-sm font-semibold text-gray-900">{blog.blog_name}</h2>
                                    <a
                                        href={blog.blog_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-xs text-indigo-500 hover:underline truncate block mt-0.5"
                                    >
                                        {blog.blog_url}
                                    </a>
                                </div>

                                <div className="flex items-center justify-between text-xs text-gray-400">
                                    <span>Last synced: {formatDate(blog.last_synced_at)}</span>
                                </div>

                                <button
                                    onClick={handleConnect}
                                    disabled={processing}
                                    className="mt-auto text-xs font-medium text-indigo-600 hover:text-indigo-800 disabled:opacity-50 text-left transition-colors"
                                >
                                    Sync Now →
                                </button>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
