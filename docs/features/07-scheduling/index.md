# Phase 7 — Scheduling ⏳

## Overview

Users can schedule posts to be published at a future date and time, with timezone defaulting to IST (Asia/Kolkata). A `posts:publish-due` Artisan command runs every minute via cron, finds due scheduled posts, and dispatches `PublishScheduledPostJob` for each. After publishing to Blogger, the job optionally triggers social shares and fires a `PostPublished` notification.

## Capabilities

- Set a `scheduled_at` datetime per post (timezone-aware, stored as UTC).
- UI datetime picker defaults to IST; value is converted to UTC before storage.
- Scheduled posts appear with `status = SCHEDULED` in the post list with a clock badge.
- Cancel a schedule by clearing `scheduled_at` (reverts to `DRAFT`).
- Cron command `posts:publish-due` polls every minute — zero missed publishes even under load.
- After publish: optional social share to all connected platforms, then `PostPublished` notification.

## Components

| Type | Name |
|---|---|
| Service | `App\Services\PostScheduler` |
| Job | `App\Jobs\PublishScheduledPostJob` |
| Command | `App\Console\Commands\PublishDuePostsCommand` (`posts:publish-due`) |
| Controller | `App\Http\Controllers\PostController` (schedule action) |
| React Component | Datetime picker in `Posts/Show.jsx` |

## DB Fields (posts — scheduling columns)

| Column | Type | Notes |
|---|---|---|
| `scheduled_at` | `timestamp` | Nullable; stored UTC |
| `status` | enum | `LIVE`, `DRAFT`, `SCHEDULED` |

## Scheduling Flow

```
[Cron: * * * * *]
        │
        ▼
posts:publish-due command
        │
        ▼
PostScheduler::getDuePosts()
  WHERE status = 'SCHEDULED'
    AND scheduled_at <= NOW()
        │
        ▼ (one per post)
PublishScheduledPostJob dispatched
        │
        ├─► BloggerService::publishPost($blogId, $postId)
        │         │
        │         ▼
        │   post.status = 'LIVE'
        │   post.published_at = now()
        │   post.scheduled_at = null
        │
        ├─► SocialShareManager::sharePost() [if platforms configured]
        │
        └─► PostPublished notification fired
```

## Timezone Handling

- All `scheduled_at` values are stored as UTC in the database.
- The frontend datetime picker displays and accepts values in IST (`Asia/Kolkata`, UTC+5:30).
- Laravel's `app.timezone` is set to `UTC`; conversions happen in the React component and in `PostScheduler`.
- Example: user picks `2026-07-01 09:00 IST` → stored as `2026-07-01 03:30:00 UTC`.

## Cron Registration (app/Console/Kernel.php)

```php
$schedule->command('posts:publish-due')->everyMinute();
```

Server cron entry:
```
* * * * * cd /path/to/blogify && php artisan schedule:run >> /dev/null 2>&1
```

## Security Notes

- `PublishScheduledPostJob` verifies the post still belongs to an active user with a valid Google token before calling the Blogger API.
- If the Google token is invalid at publish time, the job fails (not retried), `scheduled_at` is preserved, and a notification is sent asking the user to reconnect.
- Posts are never published to another user's Blogger account — `post.user_id` is always verified inside the job.

## Test Cases

- [ ] Setting `scheduled_at` on a post changes `status` to `SCHEDULED`
- [ ] Clearing `scheduled_at` reverts `status` to `DRAFT`
- [ ] `posts:publish-due` command finds posts where `scheduled_at <= now()` and `status = SCHEDULED`
- [ ] `PublishScheduledPostJob` calls `BloggerService::publishPost()` with correct IDs
- [ ] After publish: `post.status` is updated to `LIVE`, `scheduled_at` is nulled
- [ ] After publish: `PostPublished` notification is dispatched
- [ ] After publish: `ShareToSocialJob` is dispatched for each configured platform
- [ ] Posts with `scheduled_at` in the future are NOT published by the command
- [ ] Job handles invalid Google token gracefully (fails, notifies user, does not retry)
- [ ] IST datetime entered in UI is stored as correct UTC value
- [ ] Scheduled post badge is visible in the post list with correct time in IST
