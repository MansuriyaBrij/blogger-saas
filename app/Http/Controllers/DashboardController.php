<?php

namespace App\Http\Controllers;

use App\Models\BloggerAccount;
use App\Models\Label;
use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $selectedBlogId = session('selected_blog_id') ?? $user->bloggerAccounts()->first()?->id;
        $selectedBlog = $selectedBlogId ? BloggerAccount::find($selectedBlogId) : null;

        $stats = [
            'total_posts' => Post::where('blogger_account_id', $selectedBlogId)->count(),
            'live_posts' => Post::where('blogger_account_id', $selectedBlogId)->where('status', 'LIVE')->count(),
            'draft_posts' => Post::where('blogger_account_id', $selectedBlogId)->where('status', 'DRAFT')->count(),
            'total_labels' => Label::where('blogger_account_id', $selectedBlogId)->count(),
        ];

        $recentPosts = Post::where('blogger_account_id', $selectedBlogId)
            ->latest()
            ->take(5)
            ->get(['id', 'title', 'status', 'published_at']);

        return Inertia::render('Dashboard', compact('selectedBlog', 'stats', 'recentPosts'));
    }
}
