<?php

namespace App\Http\Controllers;

use App\Jobs\SyncBlogPostsJob;
use App\Models\BloggerAccount;
use App\Services\BloggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Blogs/Index', [
            'blogs' => auth()->user()->bloggerAccounts,
        ]);
    }

    public function connect(): RedirectResponse
    {
        $user = auth()->user();
        $service = app()->makeWith(BloggerService::class, ['user' => $user]);
        $remoteBlogs = $service->getBlogs();

        foreach ($remoteBlogs as $blogData) {
            $bloggerAccount = BloggerAccount::updateOrCreate(
                ['user_id' => $user->id, 'blog_id' => $blogData['id']],
                [
                    'blog_name' => $blogData['name'],
                    'blog_url' => $blogData['url'],
                    'is_active' => true,
                ]
            );

            SyncBlogPostsJob::dispatch($user, $bloggerAccount);
        }

        return redirect()->back()->with('success', 'Blogs connected!');
    }

    public function switchBlog(Request $request): JsonResponse
    {
        $request->validate([
            'blog_id' => ['required', 'integer'],
        ]);

        $exists = auth()->user()->bloggerAccounts()->where('id', $request->blog_id)->exists();

        abort_unless($exists, 403, 'Blog not found.');

        session(['selected_blog_id' => $request->blog_id]);

        return response()->json(['success' => true]);
    }
}
