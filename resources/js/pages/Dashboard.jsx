import AppLayout from '@/Layouts/AppLayout';

const statusStyles = {
    LIVE: 'bg-green-100 text-green-700',
    DRAFT: 'bg-gray-100 text-gray-600',
    SCHEDULED: 'bg-yellow-100 text-yellow-700',
};

function StatCard({ label, value }) {
    return (
        <div className="bg-white rounded-xl border border-gray-100 p-5">
            <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">{label}</p>
            <p className="text-3xl font-bold text-gray-900 mt-1">{value ?? 0}</p>
        </div>
    );
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

export default function Dashboard({ selectedBlog, stats, recentPosts }) {
    if (!selectedBlog) {
        return (
            <AppLayout>
                <div className="flex flex-col items-center justify-center h-full py-32 text-center">
                    <div className="w-14 h-14 bg-indigo-50 rounded-full flex items-center justify-center mb-4">
                        <svg className="w-7 h-7 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
                        </svg>
                    </div>
                    <h2 className="text-base font-semibold text-gray-900 mb-1">No blog selected</h2>
                    <p className="text-sm text-gray-500">
                        Connect a blog to get started —{' '}
                        <a href="/blogs" className="text-indigo-600 hover:underline">Go to Blogs</a>
                    </p>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <div className="max-w-5xl mx-auto space-y-6">
                {/* Page heading */}
                <div>
                    <h1 className="text-xl font-semibold text-gray-900">{selectedBlog.blog_name}</h1>
                    <a
                        href={selectedBlog.blog_url}
                        target="_blank"
                        rel="noreferrer"
                        className="text-sm text-indigo-500 hover:underline"
                    >
                        {selectedBlog.blog_url}
                    </a>
                </div>

                {/* Stat cards */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <StatCard label="Total Posts" value={stats.total_posts} />
                    <StatCard label="Live" value={stats.live_posts} />
                    <StatCard label="Drafts" value={stats.draft_posts} />
                    <StatCard label="Labels" value={stats.total_labels} />
                </div>

                {/* Recent posts */}
                <div className="bg-white rounded-xl border border-gray-100">
                    <div className="px-5 py-4 border-b border-gray-50">
                        <h2 className="text-sm font-semibold text-gray-900">Recent Posts</h2>
                    </div>

                    {recentPosts.length === 0 ? (
                        <div className="px-5 py-10 text-center text-sm text-gray-400">No posts synced yet.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs font-medium text-gray-400 uppercase tracking-wide border-b border-gray-50">
                                    <th className="px-5 py-3">Title</th>
                                    <th className="px-5 py-3">Status</th>
                                    <th className="px-5 py-3">Published</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {recentPosts.map((post) => (
                                    <tr key={post.id} className="hover:bg-gray-50 transition-colors">
                                        <td className="px-5 py-3 font-medium text-gray-900 truncate max-w-xs">{post.title}</td>
                                        <td className="px-5 py-3">
                                            <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${statusStyles[post.status] ?? statusStyles.DRAFT}`}>
                                                {post.status}
                                            </span>
                                        </td>
                                        <td className="px-5 py-3 text-gray-500">{formatDate(post.published_at)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
