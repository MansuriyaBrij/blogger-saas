<?php

namespace App\Http\Controllers;

use App\Jobs\DeleteLabelJob;
use App\Jobs\MergeLabelJob;
use App\Jobs\RenameLabelJob;
use App\Models\Label;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LabelController extends Controller
{
    public function index()
    {
        $blogId = session('selected_blog_id');

        $labels = Label::where('blogger_account_id', $blogId)
            ->orderByDesc('post_count')
            ->get();

        return Inertia::render('Labels/Index', [
            'labels' => $labels,
        ]);
    }

    public function rename(Request $request, Label $label)
    {
        abort_unless($label->user_id === auth()->id(), 403);

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        dispatch(new RenameLabelJob($label, $request->name));

        return back()->with('success', 'Label rename queued');
    }

    public function merge(Request $request)
    {
        $userId = auth()->id();

        $request->validate([
            'source_id' => ['required', 'integer'],
            'target_id' => ['required', 'integer'],
        ]);

        $source = Label::where('id', $request->source_id)->where('user_id', $userId)->firstOrFail();
        $target = Label::where('id', $request->target_id)->where('user_id', $userId)->firstOrFail();

        dispatch(new MergeLabelJob($source, $target));

        return back()->with('success', 'Label merge queued');
    }

    public function destroy(Label $label)
    {
        abort_unless($label->user_id === auth()->id(), 403);

        dispatch(new DeleteLabelJob($label));

        return back()->with('success', 'Label delete queued');
    }
}
