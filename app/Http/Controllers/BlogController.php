<?php

namespace App\Http\Controllers;

use App\Exceptions\BloggerApiException;
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
            'blogs' => auth()->user()->bloggerAccounts()->orderBy('blog_name')->get(),
        ]);
    }

    public function connect(): RedirectResponse
    {
        $user = auth()->user();

        if (! $user->google_access_token) {
            return redirect()->route('auth.google.redirect');
        }

        $service = app()->makeWith(BloggerService::class, ['user' => $user]);

        try {
            $remoteBlogs = $service->getBlogs();
        } catch (BloggerApiException $e) {
            return redirect()->route('auth.google.redirect');
        }

        if (empty($remoteBlogs)) {
            return redirect()->route('blogs.index')->with('error', 'No Blogger blogs found on your Google account.');
        }

        foreach ($remoteBlogs as $blogData) {
            $bloggerAccount = BloggerAccount::updateOrCreate(
                ['user_id' => $user->id, 'blog_id' => $blogData['id']],
                [
                    'blog_name' => $blogData['name'],
                    'blog_url'  => $blogData['url'],
                    'is_active' => true,
                ]
            );

            SyncBlogPostsJob::dispatch($user, $bloggerAccount);
        }

        $count = count($remoteBlogs);

        return redirect()->route('blogs.index')->with('success', "Connected {$count} blog(s). Posts are syncing in the background.");
    }

    public function sync(BloggerAccount $bloggerAccount): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($bloggerAccount->user_id === $user->id, 403);

        SyncBlogPostsJob::dispatch($user, $bloggerAccount);

        return redirect()->back()->with('success', "Syncing \"{$bloggerAccount->blog_name}\" in the background.");
    }

    public function destroy(BloggerAccount $bloggerAccount): RedirectResponse
    {
        abort_unless($bloggerAccount->user_id === auth()->id(), 403);

        $bloggerAccount->delete();

        return redirect()->back()->with('success', "Blog \"{$bloggerAccount->blog_name}\" disconnected.");
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
