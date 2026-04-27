<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareBlogsToInertia
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            Inertia::share([
                'blogs' => fn () => auth()->user()->bloggerAccounts()->get(['id', 'blog_name', 'blog_url']),
                'selected_blog_id' => fn () => session('selected_blog_id'),
            ]);
        }

        return $next($request);
    }
}
