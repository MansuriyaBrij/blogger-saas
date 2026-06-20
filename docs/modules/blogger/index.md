# Blogger Module

## Responsibility

Wraps the Google Blogger v3 API (`google/apiclient`) and owns all sync logic. Provides a clean service interface so the rest of the app never calls the Blogger SDK directly.

## Classes

| Class | Role |
|---|---|
| `App\Services\BloggerService` | Core API wrapper; `getBlogs()`, `getPosts()`, `getPost()`, `publishPost()`, `revertToDraft()`, `updatePost()`, `deletePost()` |
| `App\Http\Controllers\BlogController` | HTTP actions: `index`, `connect`, `sync`, `destroy`, `switchBlog` |
| `App\Jobs\ConnectBlogJob` | (Planned) async blog connection for large accounts |
| `App\Jobs\SyncBlogPostsJob` | Paginated post sync + label count upsert; stamps `last_synced_at` |
| `App\Jobs\SyncBlogLabelsJob` | Standalone label-only re-sync job |
| `App\Models\BloggerAccount` | Represents one connected Blogger blog |
| `App\Exceptions\BloggerApiException` | Wraps Google API errors into a domain exception |

## Key Methods

| Method | Class | Description |
|---|---|---|
| `getBlogs(): array` | `BloggerService` | Returns `[id, name, url]` for all blogs on the account |
| `getPosts(string $blogId): array` | `BloggerService` | Paginates all posts (500/page); supports `updatedMin` for incremental sync |
| `getPost(string $blogId, string $postId): array` | `BloggerService` | Single post fetch |
| `publishPost(string $blogId, string $postId): void` | `BloggerService` | Calls `posts.publish` |
| `revertToDraft(string $blogId, string $postId): void` | `BloggerService` | Calls `posts.revert` |
| `updatePost(string $blogId, string $postId, array $data): void` | `BloggerService` | Calls `posts.patch` |
| `deletePost(string $blogId, string $postId): void` | `BloggerService` | Calls `posts.delete` |
| `handle()` | `SyncBlogPostsJob` | Full sync loop; fires `SyncCompleted` event when done |

## Models

### BloggerAccount

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | bigint | FK → users, cascade delete |
| `blog_id` | string | Blogger blog ID (external) |
| `blog_name` | string | Display name |
| `blog_url` | string | Public blog URL |
| `is_active` | boolean | Soft-disable without deleting |
| `last_synced_at` | timestamp | Used as `updatedMin` in incremental syncs |

## Notes

- `BloggerService` is resolved via `app()->makeWith(BloggerService::class, ['user' => $user])` — never via the container directly — so the correct user's tokens are always used.
- `Google_Service_Exception` is caught inside every service method and re-thrown as `BloggerApiException` to keep Google SDK types out of the HTTP layer.
- `SyncBlogPostsJob` has `$tries = 3`, `$timeout = 120`. It runs in the `sync` Horizon queue.
- Cascade deletes: `blogger_accounts` → `posts` → (labels via `blogger_account_id`).
