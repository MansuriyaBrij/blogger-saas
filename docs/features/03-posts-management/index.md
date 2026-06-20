# Phase 3 — Posts Management ✅

## Overview

The Posts module gives users full control over their synced blog posts: view, search, filter, edit, publish, revert to draft, and delete — all reflected back to Blogger in real time. The Label Manager lets users rename, merge, and delete labels across their entire blog. Bulk operations allow mass status changes and deletes via a background job. The dashboard provides a blog-switcher for users with multiple connected blogs.

## User Flow

1. User navigates to `/posts` — sees a paginated, filterable list of posts for the active blog.
2. Search and filters (status, label) trigger Inertia partial reloads — no full page refresh.
3. Clicking a post opens `/posts/{id}` with an inline editor (title, content, labels).
4. Saving dispatches `UpdatePostOnBloggerJob` to push changes to the Blogger API.
5. Toggling status (`LIVE` ↔ `DRAFT`) dispatches `PublishPostJob` or `RevertToDraftJob`.
6. Deleting a post dispatches `DeletePostOnBloggerJob` then soft-deletes the local record.
7. On `/labels`, user can rename a label (renames across all posts), merge two labels, or delete a label (removes from all posts).
8. Bulk mode: user selects posts via checkboxes, picks an action, confirms — `BulkPostActionJob` runs in background.
9. The **blog switcher** in `AppLayout` sets `session('selected_blog_id')` and reloads the current page scoped to that blog.

## Components

| Type | Name |
|---|---|
| Controller | `App\Http\Controllers\PostController` |
| Controller | `App\Http\Controllers\LabelController` |
| Controller | `App\Http\Controllers\BulkController` |
| Controller | `App\Http\Controllers\DashboardController` |
| Service | `App\Services\BloggerService` |
| Job | `App\Jobs\UpdatePostOnBloggerJob` |
| Job | `App\Jobs\PublishPostJob` |
| Job | `App\Jobs\RevertToDraftJob` |
| Job | `App\Jobs\DeletePostOnBloggerJob` |
| Job | `App\Jobs\BulkPostActionJob` |
| Job | `App\Jobs\RenameLabelJob` |
| Job | `App\Jobs\MergeLabelJob` |
| Job | `App\Jobs\DeleteLabelJob` |
| Model | `App\Models\Post` |
| Model | `App\Models\Label` |
| Model | `App\Models\BulkOperation` |
| React Page | `resources/js/pages/Posts/Index.jsx` |
| React Page | `resources/js/pages/Posts/Show.jsx` |
| React Page | `resources/js/pages/Labels/Index.jsx` |
| React Page | `resources/js/pages/dashboard.tsx` |
| React Component | `resources/js/layouts/app-layout.tsx` (blog switcher) |

## API Endpoints

| Method | Route | Action |
|---|---|---|
| GET | `/posts` | Paginated post list with search/filter |
| GET | `/posts/{post}` | Single post detail + editor |
| PUT | `/posts/{post}` | Update title, content, labels |
| POST | `/posts/{post}/toggle-status` | Publish or revert to draft |
| DELETE | `/posts/{post}` | Delete post locally + on Blogger |
| GET | `/labels` | Label list with post counts |
| PUT | `/labels/{label}/rename` | Rename label across all posts |
| POST | `/labels/merge` | Merge source label into target |
| DELETE | `/labels/{label}` | Remove label from all posts + delete |
| POST | `/bulk` | Initiate bulk operation |
| GET | `/bulk/{operation}/status` | Poll bulk job progress |

## Inertia Partial Reloads

Search and filter inputs use `router.get('/posts', filters, { only: ['posts'], preserveState: true })` so only the posts prop is refreshed — the blog switcher and sidebar remain mounted.

## Bulk Operations

| Action | Job |
|---|---|
| Publish selected | `BulkPostActionJob` → `PublishPostJob` per post |
| Revert to draft | `BulkPostActionJob` → `RevertToDraftJob` per post |
| Delete selected | `BulkPostActionJob` → `DeletePostOnBloggerJob` per post |

Progress is tracked in `bulk_operations` table and polled from the frontend via `GET /bulk/{id}/status`.

## Security Notes

- All post/label queries are scoped to `auth()->user()->id` — no cross-tenant data leakage.
- Bulk operation ownership is verified before dispatch.
- Post content is not sanitised server-side (Blogger accepts raw HTML); XSS risk is on Blogger's domain, not Blogify's.

## Test Cases

- [ ] `GET /posts` returns only posts belonging to the authenticated user's active blog
- [ ] Search filter returns matching posts, non-matching posts excluded
- [ ] Status filter (`LIVE`, `DRAFT`) returns correct subset
- [ ] Label filter scopes posts to those containing the selected label
- [ ] `PUT /posts/{id}` updates title, content, and labels in DB and dispatches Blogger update job
- [ ] `POST /posts/{id}/toggle-status` dispatches `PublishPostJob` for DRAFT→LIVE
- [ ] `POST /posts/{id}/toggle-status` dispatches `RevertToDraftJob` for LIVE→DRAFT
- [ ] `DELETE /posts/{id}` dispatches delete job and removes local record
- [ ] Non-owner cannot edit or delete another user's post (403)
- [ ] `PUT /labels/{id}/rename` renames label on all associated posts
- [ ] `POST /labels/merge` moves all posts from source label to target and deletes source
- [ ] `DELETE /labels/{id}` removes label from all posts and deletes it
- [ ] Bulk publish dispatches one job per selected post
- [ ] `GET /bulk/{id}/status` returns correct progress percentage
- [ ] Blog switcher sets session and scopes subsequent post queries to new blog
