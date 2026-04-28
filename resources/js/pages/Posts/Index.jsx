import AppLayout from '@/Layouts/AppLayout';
import { Link, router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

const STATUS_STYLES = {
    LIVE:      'bg-green-100 text-green-700',
    SCHEDULED: 'bg-yellow-100 text-yellow-700',
    DRAFT:     'bg-gray-100 text-gray-600',
};

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

export default function PostsIndex({ posts, filters, selected_blog }) {
    const { flash } = usePage().props;
    const [selected, setSelected]       = useState([]);
    const [bulkAction, setBulkAction]   = useState('');
    const [labelInput, setLabelInput]   = useState('');
    const [bulkLoading, setBulkLoading] = useState(false);
    const [search, setSearch]           = useState(filters.search ?? '');

    const allIds = posts.data.map((p) => p.id);
    const allChecked = allIds.length > 0 && allIds.every((id) => selected.includes(id));

    function toggleAll() {
        setSelected(allChecked ? [] : allIds);
    }

    function toggleOne(id) {
        setSelected((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
    }

    function applyFilter(key, value) {
        router.get('/posts', { ...filters, [key]: value || undefined, page: 1 }, { preserveState: true, replace: true });
    }

    function submitSearch(e) {
        e.preventDefault();
        applyFilter('search', search);
    }

    function handleDelete(postId) {
        if (!confirm('Delete this post?')) return;
        router.delete(`/posts/${postId}`);
    }

    function handleToggle(postId) {
        router.post(`/posts/${postId}/toggle-status`);
    }

    async function handleBulk() {
        if (!bulkAction || selected.length === 0) return;
        if (bulkAction === 'delete' && !confirm(`Delete ${selected.length} posts?`)) return;

        setBulkLoading(true);
        try {
            await axios.post('/bulk', {
                action:     bulkAction,
                post_ids:   selected,
                label_name: labelInput || undefined,
            });
            setSelected([]);
            setBulkAction('');
            setLabelInput('');
            router.reload();
        } finally {
            setBulkLoading(false);
        }
    }

    const needsLabel = bulkAction === 'label_add' || bulkAction === 'label_remove';

    return (
        <AppLayout>
            <div className="max-w-6xl mx-auto">
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-xl font-semibold text-gray-900">Posts</h1>
                        {selected_blog && (
                            <p className="text-sm text-gray-500 mt-0.5">{selected_blog.blog_name}</p>
                        )}
                    </div>
                </div>

                {/* Flash */}
                {flash?.success && (
                    <div className="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                        {flash.error}
                    </div>
                )}

                {/* Filters */}
                <div className="bg-white rounded-xl border border-gray-100 p-4 mb-4 flex flex-wrap gap-3">
                    <form onSubmit={submitSearch} className="flex gap-2 flex-1 min-w-48">
                        <input
                            type="text"
                            placeholder="Search title…"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                        <button type="submit" className="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">
                            Search
                        </button>
                    </form>

                    <select
                        value={filters.status ?? ''}
                        onChange={(e) => applyFilter('status', e.target.value)}
                        className="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">All statuses</option>
                        <option value="LIVE">Live</option>
                        <option value="DRAFT">Draft</option>
                        <option value="SCHEDULED">Scheduled</option>
                    </select>

                    <input
                        type="text"
                        placeholder="Filter by label…"
                        defaultValue={filters.label ?? ''}
                        onBlur={(e) => applyFilter('label', e.target.value)}
                        className="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />

                    <input
                        type="date"
                        value={filters.date_from ?? ''}
                        onChange={(e) => applyFilter('date_from', e.target.value)}
                        className="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                    <input
                        type="date"
                        value={filters.date_to ?? ''}
                        onChange={(e) => applyFilter('date_to', e.target.value)}
                        className="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                </div>

                {/* Bulk action bar */}
                {selected.length > 0 && (
                    <div className="bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-3 mb-4 flex flex-wrap items-center gap-3">
                        <span className="text-sm font-medium text-indigo-700">{selected.length} post{selected.length > 1 ? 's' : ''} selected</span>

                        <select
                            value={bulkAction}
                            onChange={(e) => { setBulkAction(e.target.value); setLabelInput(''); }}
                            className="text-sm border border-indigo-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Choose action…</option>
                            <option value="publish">Publish</option>
                            <option value="draft">Revert to Draft</option>
                            <option value="delete">Delete</option>
                            <option value="label_add">Add Label</option>
                            <option value="label_remove">Remove Label</option>
                        </select>

                        {needsLabel && (
                            <input
                                type="text"
                                placeholder="Label name…"
                                value={labelInput}
                                onChange={(e) => setLabelInput(e.target.value)}
                                className="text-sm border border-indigo-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        )}

                        <button
                            onClick={handleBulk}
                            disabled={bulkLoading || !bulkAction || (needsLabel && !labelInput)}
                            className="px-4 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {bulkLoading ? 'Processing…' : 'Apply'}
                        </button>
                        <button onClick={() => setSelected([])} className="text-sm text-indigo-500 hover:underline">
                            Clear
                        </button>
                    </div>
                )}

                {/* Table */}
                <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                    {posts.data.length === 0 ? (
                        <div className="py-20 text-center text-sm text-gray-400">No posts found.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th className="w-10 px-4 py-3">
                                        <input type="checkbox" checked={allChecked} onChange={toggleAll} />
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">Title</th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">Labels</th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-500">Published</th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {posts.data.map((post) => (
                                    <tr key={post.id} className="hover:bg-gray-50 transition-colors">
                                        <td className="px-4 py-3">
                                            <input
                                                type="checkbox"
                                                checked={selected.includes(post.id)}
                                                onChange={() => toggleOne(post.id)}
                                            />
                                        </td>
                                        <td className="px-4 py-3 max-w-xs">
                                            <Link
                                                href={`/posts/${post.id}`}
                                                className="font-medium text-gray-900 hover:text-indigo-600 truncate block"
                                            >
                                                {post.title}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${STATUS_STYLES[post.status] ?? 'bg-gray-100 text-gray-600'}`}>
                                                {post.status}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap gap-1">
                                                {(post.labels ?? []).slice(0, 3).map((l) => (
                                                    <span key={l} className="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-full text-xs">
                                                        {l}
                                                    </span>
                                                ))}
                                                {(post.labels ?? []).length > 3 && (
                                                    <span className="text-xs text-gray-400">+{post.labels.length - 3}</span>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-gray-500">{formatDate(post.published_at)}</td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link
                                                    href={`/posts/${post.id}`}
                                                    className="text-xs font-medium text-indigo-600 hover:underline"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    onClick={() => handleToggle(post.id)}
                                                    className="text-xs font-medium text-gray-500 hover:text-gray-700"
                                                >
                                                    {post.status === 'LIVE' ? 'Draft' : 'Publish'}
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(post.id)}
                                                    className="text-xs font-medium text-red-500 hover:text-red-700"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>

                {/* Pagination */}
                {posts.last_page > 1 && (
                    <div className="mt-4 flex justify-center gap-1">
                        {posts.links.map((link, i) => (
                            <button
                                key={i}
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={`px-3 py-1.5 text-sm rounded-lg border transition-colors
                                    ${link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}
                                    ${!link.url ? 'opacity-40 cursor-default' : ''}`}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
