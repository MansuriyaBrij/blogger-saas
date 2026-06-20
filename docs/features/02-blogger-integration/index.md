# Phase 2 — Blogger Integration ✅

## Overview

After authentication, the user connects their Google Blogger account. Blogify calls the Blogger API (via `google/apiclient`) to list all blogs, stores them as `BloggerAccount` records, then dispatches background jobs to sync posts and labels. Incremental re-syncs use the Blogger API `updatedMin` parameter so only changed posts are fetched.

## User Flow

1. User visits `/blogs` and clicks **Connect Blogs**.
2. `BlogController@connect` calls `BloggerService::getBlogs()` via the Google API.
3. Each returned blog is upserted into `blogger_accounts` (`updateOrCreate` on `blog_id`).
4. `SyncBlogPostsJob` is dispatched per blog into the `sync` Horizon queue.
5. Inside the job, `BloggerService::getPosts()` paginates through all posts (500 per page).
6. Posts are upserted into `posts`; labels are counted and upserted into `labels`.
7. `BloggerAccount.last_synced_at` is stamped; `SyncCompleted` event is broadcast.
8. User clicks **Sync Now** on any card to trigger a fresh `SyncBlogPostsJob` for that blog.
9. User can **Disconnect** a blog — cascades delete to all its posts and labels.

## Components

| Type | Name |
|---|---|
| Service | `App\Services\BloggerService` |
| Controller | `App\Http\Controllers\BlogController` |
| Job | `App\Jobs\ConnectBlogJob` |
| Job | `App\Jobs\SyncBlogPostsJob` |
| Job | `App\Jobs\SyncBlogLabelsJob` |
| Model | `App\Models\BloggerAccount` |
| Model | `App\Models\Post` |
| Model | `App\Models\Label` |
| React Page | `resources/js/pages/Blogs/Index.jsx` |

## Blogger API Endpoints Used

| Endpoint | Method | Purpose |
|---|---|---|
| `blogs.listByUser('self')` | GET | List all blogs owned by the authenticated user |
| `posts.list($blogId, $params)` | GET | Paginated post list; supports `updatedMin`, `maxResults`, `pageToken` |
| `posts.get($blogId, $postId)` | GET | Single post fetch for detail/update diff |

## Incremental Sync

- On first sync: all posts are fetched (no `updatedMin`).
- On subsequent syncs: `updatedMin` is set to `BloggerAccount.last_synced_at` in ISO 8601 format.
- `updateOrCreate` keyed on `blogger_post_id` ensures no duplicates.
- Posts deleted on Blogger are **not** automatically removed — a future cleanup job will handle tombstoning.

## Horizon Queue Config

| Queue | Workers | Retries | Timeout |
|---|---|---|---|
| `sync` | 2 | 3 | 120s |
| `default` | 1 | 3 | 60s |

## Security Notes

- Blog ownership is verified before every sync/disconnect: `abort_unless($account->user_id === auth()->id(), 403)`.
- API errors from `google/apiclient` throw `BloggerApiException` which is caught in controllers — no raw Google exceptions leak to the frontend.
- Token expiry is checked before every `BloggerService` call; stale tokens redirect to re-auth.

## Test Cases

- [ ] `GET /blogs` returns blogs belonging to the authenticated user only
- [ ] `POST /blogs/connect` with valid token fetches and upserts all blogs
- [ ] `POST /blogs/connect` with missing token redirects to Google OAuth
- [ ] `POST /blogs/connect` with no Blogger blogs returns an error flash
- [ ] `SyncBlogPostsJob` upserts posts correctly on first run
- [ ] `SyncBlogPostsJob` uses `updatedMin` on subsequent runs
- [ ] `SyncBlogPostsJob` upserts label counts in `labels` table
- [ ] `BloggerAccount.last_synced_at` is updated after successful sync
- [ ] `POST /blogs/{id}/sync` dispatches job only for the owner's blog
- [ ] `DELETE /blogs/{id}` cascades delete to posts and labels
- [ ] Non-owner cannot sync or delete another user's blog (403)
