# Posts Module

## Responsibility

Owns the local mirror of Blogger posts — CRUD, status toggling, search/filter, and all background jobs that push changes back to the Blogger API.

## Classes

| Class | Role |
|---|---|
| `App\Http\Controllers\PostController` | `index`, `show`, `update`, `toggleStatus`, `destroy` HTTP actions |
| `App\Jobs\UpdatePostOnBloggerJob` | Calls `BloggerService::updatePost()` after local save |
| `App\Jobs\PublishPostJob` | Calls `BloggerService::publishPost()`; updates local status |
| `App\Jobs\RevertToDraftJob` | Calls `BloggerService::revertToDraft()`; updates local status |
| `App\Jobs\DeletePostOnBloggerJob` | Calls `BloggerService::deletePost()`; then deletes local record |
| `App\Jobs\BulkPostActionJob` | Coordinates mass operations; tracks progress in `bulk_operations` |
| `App\Models\Post` | Eloquent model; belongs to `User` and `BloggerAccount` |
| `App\Models\BulkOperation` | Tracks bulk job progress (`total`, `processed`, `failed`) |

## Key Methods

| Method | Class | Description |
|---|---|---|
| `index(Request)` | `PostController` | Paginated list; accepts `search`, `status`, `label` filters; Inertia partial reload on `posts` only |
| `show(Post)` | `PostController` | Single post with full content; bound by route model binding scoped to `auth()->user()` |
| `update(Request, Post)` | `PostController` | Validates + saves locally; dispatches `UpdatePostOnBloggerJob` |
| `toggleStatus(Request, Post)` | `PostController` | Dispatches `PublishPostJob` or `RevertToDraftJob` based on current status |
| `destroy(Post)` | `PostController` | Dispatches `DeletePostOnBloggerJob`; soft-deletes or hard-deletes local record |
| `handle()` | `BulkPostActionJob` | Loops through post IDs; dispatches sub-jobs; increments `bulk_operations.processed` |

## Models

### Post

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | bigint | FK → users |
| `blogger_account_id` | bigint | FK → blogger_accounts |
| `blogger_post_id` | string | External Blogger post ID |
| `title` | string | — |
| `content` | longText | Raw HTML from Blogger |
| `url` | string | Nullable; public post URL |
| `labels` | json | Array of label name strings |
| `status` | enum | `LIVE`, `DRAFT`, `SCHEDULED` |
| `published_at` | timestamp | Nullable; UTC |
| `scheduled_at` | timestamp | Nullable; UTC; used by scheduler |
| `synced_at` | timestamp | Last sync from Blogger |

### BulkOperation

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | bigint | FK → users |
| `action` | string | `publish`, `draft`, `delete` |
| `total` | integer | Total posts in batch |
| `processed` | integer | Completed so far |
| `failed` | integer | Failed items |
| `status` | enum | `pending`, `running`, `done`, `failed` |

## Notes

- All Post queries are scoped by `user_id` — no raw `Post::find()` calls in controllers.
- Route model binding uses `resolveRouteBindingQuery` to add `where('user_id', auth()->id())` scope automatically.
- `labels` column is a JSON array stored as-is from the Blogger API — no separate pivot table.
- Inertia partial reloads use `only: ['posts']` to avoid re-fetching the sidebar blog list on every filter change.
