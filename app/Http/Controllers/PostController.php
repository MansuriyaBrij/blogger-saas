<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePostRequest;
use App\Jobs\DeletePostOnBloggerJob;
use App\Jobs\PublishPostJob;
use App\Jobs\RevertToDraftJob;
use App\Jobs\UpdatePostOnBloggerJob;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $blogId = session('selected_blog_id');

        $query = Post::where('blogger_account_id', $blogId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('label')) {
            $query->whereJsonContains('labels', $request->label);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('published_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('published_at', '<=', $request->date_to);
        }

        $posts = $query->orderByDesc('published_at')->paginate(20)->withQueryString();

        $selectedBlog = $blogId
            ? auth()->user()->bloggerAccounts()->find($blogId)
            : null;

        return Inertia::render('Posts/Index', [
            'posts' => $posts,
            'filters' => $request->only(['status', 'label', 'search', 'date_from', 'date_to']),
            'selected_blog' => $selectedBlog,
        ]);
    }

    public function show(Post $post)
    {
        abort_unless($post->user_id === auth()->id(), 403);

        return Inertia::render('Posts/Show', [
            'post' => $post,
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        abort_unless($post->user_id === auth()->id(), 403);

        $post->update($request->validated());

        dispatch(new UpdatePostOnBloggerJob($post));

        return back()->with('success', 'Post updated');
    }

    public function toggleStatus(Post $post)
    {
        abort_unless($post->user_id === auth()->id(), 403);

        if ($post->status === 'LIVE') {
            dispatch(new RevertToDraftJob($post));
        } else {
            dispatch(new PublishPostJob($post));
        }

        return back()->with('success', 'Status updated');
    }

    public function destroy(Post $post)
    {
        abort_unless($post->user_id === auth()->id(), 403);

        dispatch(new DeletePostOnBloggerJob($post));
        $post->delete();

        return back()->with('success', 'Post deleted');
    }
}
