import AppLayout from '@/Layouts/AppLayout';
import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function LabelsIndex({ labels }) {
    const { flash } = usePage().props;
    const [renaming, setRenaming]     = useState(null);
    const [renameVal, setRenameVal]   = useState('');
    const [merging, setMerging]       = useState(null);
    const [mergeTarget, setMergeTarget] = useState('');

    function handleRename(label) {
        setRenaming(label.id);
        setRenameVal(label.name);
    }

    function submitRename(label) {
        router.put(`/labels/${label.id}/rename`, { name: renameVal }, {
            onSuccess: () => setRenaming(null),
        });
    }

    function handleMerge(label) {
        setMerging(label.id);
        setMergeTarget('');
    }

    function submitMerge(sourceId) {
        if (!mergeTarget) return;
        router.post('/labels/merge', { source_id: sourceId, target_id: Number(mergeTarget) }, {
            onSuccess: () => setMerging(null),
        });
    }

    function handleDelete(label) {
        if (!confirm(`Delete label "${label.name}"? Posts will keep their content but the label will be removed.`)) return;
        router.delete(`/labels/${label.id}`);
    }

    const otherLabels = (sourceId) => labels.filter((l) => l.id !== sourceId);

    return (
        <AppLayout>
            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <h1 className="text-xl font-semibold text-gray-900">Labels</h1>
                    <p className="text-sm text-gray-500 mt-0.5">Manage labels across your blog posts</p>
                </div>

                {flash?.success && (
                    <div className="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
                        {flash.success}
                    </div>
                )}

                {labels.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-24 text-center">
                        <div className="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <svg className="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <h3 className="text-sm font-medium text-gray-900 mb-1">No labels yet</h3>
                        <p className="text-sm text-gray-500">Labels are created when you sync posts from your blog.</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        {labels.map((label) => (
                            <div key={label.id} className="bg-white rounded-xl border border-gray-100 p-4 flex flex-col gap-3">
                                <div className="flex items-start justify-between gap-2">
                                    <div className="flex-1 min-w-0">
                                        {renaming === label.id ? (
                                            <div className="flex gap-2">
                                                <input
                                                    type="text"
                                                    value={renameVal}
                                                    onChange={(e) => setRenameVal(e.target.value)}
                                                    onKeyDown={(e) => e.key === 'Enter' && submitRename(label)}
                                                    autoFocus
                                                    className="flex-1 text-sm border border-indigo-300 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                />
                                                <button
                                                    onClick={() => submitRename(label)}
                                                    className="text-xs px-2 py-1 bg-indigo-600 text-white rounded-lg"
                                                >
                                                    Save
                                                </button>
                                                <button
                                                    onClick={() => setRenaming(null)}
                                                    className="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded-lg"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        ) : (
                                            <h3 className="text-sm font-semibold text-gray-900 truncate">{label.name}</h3>
                                        )}
                                    </div>
                                    <span className="flex-shrink-0 px-2 py-0.5 bg-indigo-50 text-indigo-600 text-xs font-medium rounded-full">
                                        {label.post_count} post{label.post_count !== 1 ? 's' : ''}
                                    </span>
                                </div>

                                {merging === label.id && (
                                    <div className="flex gap-2">
                                        <select
                                            value={mergeTarget}
                                            onChange={(e) => setMergeTarget(e.target.value)}
                                            className="flex-1 text-sm border border-gray-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        >
                                            <option value="">Merge into…</option>
                                            {otherLabels(label.id).map((t) => (
                                                <option key={t.id} value={t.id}>{t.name}</option>
                                            ))}
                                        </select>
                                        <button
                                            onClick={() => submitMerge(label.id)}
                                            disabled={!mergeTarget}
                                            className="text-xs px-2 py-1 bg-indigo-600 text-white rounded-lg disabled:opacity-50"
                                        >
                                            Merge
                                        </button>
                                        <button
                                            onClick={() => setMerging(null)}
                                            className="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded-lg"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                )}

                                <div className="flex items-center gap-2 mt-auto">
                                    <button
                                        onClick={() => handleRename(label)}
                                        className="text-xs font-medium text-indigo-600 hover:underline"
                                    >
                                        Rename
                                    </button>
                                    <span className="text-gray-200">|</span>
                                    <button
                                        onClick={() => handleMerge(label)}
                                        className="text-xs font-medium text-gray-500 hover:text-gray-700"
                                    >
                                        Merge
                                    </button>
                                    <span className="text-gray-200">|</span>
                                    <button
                                        onClick={() => handleDelete(label)}
                                        className="text-xs font-medium text-red-500 hover:text-red-700"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
