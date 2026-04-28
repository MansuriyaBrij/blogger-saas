import AppLayout from '@/Layouts/AppLayout';
import { Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function PostShow({ post }) {
    const { flash } = usePage().props;
    const { data, setData, put, processing, errors } = useForm({
        title:   post.title,
        content: post.content,
        labels:  post.labels ?? [],
        status:  post.status,
    });

    const [labelInput, setLabelInput] = useState('');

    function handleSave(e) {
        e.preventDefault();
        put(`/posts/${post.id}`);
    }

    function handleToggle() {
        if (!confirm('Toggle post status?')) return;
        useForm().post(`/posts/${post.id}/toggle-status`);
    }

    function addLabel(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            const val = labelInput.trim();
            if (val && !data.labels.includes(val)) {
                setData('labels', [...data.labels, val]);
            }
            setLabelInput('');
        }
    }

    function removeLabel(label) {
        setData('labels', data.labels.filter((l) => l !== label));
    }

    return (
        <AppLayout>
            <div className="max-w-3xl mx-auto">
                <div className="flex items-center gap-3 mb-6">
                    <Link href="/posts" className="text-sm text-gray-500 hover:text-gray-700">
                        ← Posts
                    </Link>
                    <span className="text-gray-300">/</span>
                    <h1 className="text-xl font-semibold text-gray-900 truncate">{post.title}</h1>
                </div>

                {flash?.success && (
                    <div className="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
                        {flash.success}
                    </div>
                )}

                <form onSubmit={handleSave} className="bg-white rounded-xl border border-gray-100 p-6 space-y-5">
                    {/* Title */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input
                            type="text"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                        {errors.title && <p className="text-xs text-red-500 mt-1">{errors.title}</p>}
                    </div>

                    {/* Status */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="LIVE">Live</option>
                            <option value="DRAFT">Draft</option>
                            <option value="SCHEDULED">Scheduled</option>
                        </select>
                        {errors.status && <p className="text-xs text-red-500 mt-1">{errors.status}</p>}
                    </div>

                    {/* Labels */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Labels</label>
                        <div className="flex flex-wrap gap-1.5 mb-2">
                            {data.labels.map((l) => (
                                <span key={l} className="flex items-center gap-1 px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-full text-xs">
                                    {l}
                                    <button type="button" onClick={() => removeLabel(l)} className="text-indigo-400 hover:text-indigo-700">×</button>
                                </span>
                            ))}
                        </div>
                        <input
                            type="text"
                            placeholder="Type label and press Enter…"
                            value={labelInput}
                            onChange={(e) => setLabelInput(e.target.value)}
                            onKeyDown={addLabel}
                            className="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    {/* Content */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Content</label>
                        <textarea
                            value={data.content}
                            onChange={(e) => setData('content', e.target.value)}
                            rows={16}
                            className="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                        {errors.content && <p className="text-xs text-red-500 mt-1">{errors.content}</p>}
                    </div>

                    {/* Actions */}
                    <div className="flex items-center gap-3 pt-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-60"
                        >
                            {processing ? 'Saving…' : 'Save'}
                        </button>

                        <Link
                            href={`/posts/${post.id}/toggle-status`}
                            method="post"
                            as="button"
                            className="px-5 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200"
                        >
                            {post.status === 'LIVE' ? 'Revert to Draft' : 'Publish'}
                        </Link>

                        <Link href="/posts" className="text-sm text-gray-400 hover:text-gray-600 ml-auto">
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
