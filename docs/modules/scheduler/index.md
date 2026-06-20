# Scheduler Module

## Responsibility

Manages time-based post publishing: stores `scheduled_at` (UTC) on posts, polls every minute for due posts, and dispatches the publish pipeline (Blogger → social shares → notification).

## Classes

| Class | Role |
|---|---|
| `App\Services\PostScheduler` | `getDuePosts()` — queries posts due for publish; `schedulePost()` / `cancelSchedule()` helpers |
| `App\Jobs\PublishScheduledPostJob` | Runs the full publish pipeline for one scheduled post |
| `App\Console\Commands\PublishDuePostsCommand` | Artisan command `posts:publish-due`; loops due posts and dispatches jobs |

## Key Methods

| Method | Class | Description |
|---|---|---|
| `getDuePosts(): Collection` | `PostScheduler` | `WHERE status = 'SCHEDULED' AND scheduled_at <= NOW()` |
| `schedulePost(Post, Carbon): void` | `PostScheduler` | Sets `scheduled_at` (UTC) and `status = SCHEDULED` |
| `cancelSchedule(Post): void` | `PostScheduler` | Nulls `scheduled_at`; sets `status = DRAFT` |
| `handle()` | `PublishScheduledPostJob` | Verifies user token → publishes on Blogger → updates local record → triggers social shares → fires `PostPublished` notification |
| `handle()` | `PublishDuePostsCommand` | Calls `PostScheduler::getDuePosts()` and dispatches `PublishScheduledPostJob` per result |

## DB Fields (posts — scheduler columns)

| Column | Type | Notes |
|---|---|---|
| `scheduled_at` | `timestamp` | Nullable; stored UTC; used in `getDuePosts()` query |
| `status` | enum | `LIVE`, `DRAFT`, `SCHEDULED`; set to `SCHEDULED` when `scheduled_at` is saved |

## Scheduling Flow

```
Cron (every minute)
  └─► php artisan schedule:run
        └─► posts:publish-due
              └─► PostScheduler::getDuePosts()
                    └─► [foreach due post]
                          └─► PublishScheduledPostJob::dispatch($post)
                                ├─► Verify Google token
                                ├─► BloggerService::publishPost()
                                ├─► post.status = LIVE
                                ├─► post.published_at = now()
                                ├─► post.scheduled_at = null
                                ├─► SocialShareManager::sharePost() [optional]
                                └─► PostPublished notification
```

## Timezone Handling

| Layer | Timezone |
|---|---|
| Database storage | UTC |
| Laravel app | UTC (`config/app.php timezone`) |
| Frontend display | IST (Asia/Kolkata, UTC+5:30) |
| Conversion | React datetime picker converts local IST to UTC before `PUT /posts/{id}` |

Conversion example:
- User selects `01 Jul 2026, 09:00 AM IST`
- Frontend converts: `new Date('2026-07-01T09:00:00+05:30').toISOString()` → `2026-07-01T03:30:00.000Z`
- Stored as `2026-07-01 03:30:00` UTC

## Notes

- `PublishScheduledPostJob` has `$tries = 1` — scheduling failures should not auto-retry with stale tokens. Instead notify the user.
- If `google_access_token` is invalid at publish time, the job logs an error, leaves `scheduled_at` intact (so the user can fix and re-trigger), and fires a notification.
- The Artisan scheduler must be driven by a server cron: `* * * * * php artisan schedule:run`.
- In production with Horizon, `PublishScheduledPostJob` runs in the `publishing` queue with dedicated workers.
