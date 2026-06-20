# Phase 4 — Notifications 🔜

## Overview

Blogify delivers real-time in-app notifications for long-running background events (sync complete, bulk action done, post published, social share result). Notifications are stored in the database for persistence and simultaneously broadcast over a private Soketi WebSocket channel so the bell icon updates instantly without polling.

## User Flow

1. A background job completes (e.g. `SyncBlogPostsJob`).
2. The job dispatches a Laravel Notification (e.g. `BlogSynced`) to the user.
3. The notification writes a row to the `notifications` table (`toDatabase`).
4. The notification also broadcasts on `private-user.{id}` via Soketi (`toBroadcast`).
5. `NotificationBell.jsx` is mounted in `AppLayout` — it subscribes to the channel using `useNotifications` hook on component mount.
6. On receiving a broadcast event the hook increments the unread count badge and appends the notification to the dropdown list.
7. Clicking the bell opens a dropdown of recent notifications (unread first).
8. Clicking a notification marks it read and navigates to the relevant resource.
9. **Mark all read** button sends `POST /notifications/read-all` — no page reload.

## Components

| Type | Name |
|---|---|
| Notification | `App\Notifications\BlogSynced` |
| Notification | `App\Notifications\BulkActionCompleted` |
| Notification | `App\Notifications\PostPublished` |
| Notification | `App\Notifications\SocialShareResult` |
| Controller | `App\Http\Controllers\NotificationController` |
| React Component | `resources/js/components/NotificationBell.jsx` |
| React Hook | `resources/js/hooks/useNotifications.js` |

## Notification Channels

Each notification class implements both `toDatabase` and `toBroadcast`:

```php
public function via(object $notifiable): array
{
    return ['database', 'broadcast'];
}
```

## Broadcast Channel

- Channel: `private-user.{id}` (authenticated, scoped per user).
- Driver: Soketi (`BROADCAST_DRIVER=pusher` with Soketi endpoint).
- Frontend: `laravel-echo` + `pusher-js` subscribed in `useNotifications` hook.

## WebSocket Config

| Key | Value |
|---|---|
| `BROADCAST_DRIVER` | `pusher` |
| `PUSHER_APP_HOST` | `127.0.0.1` |
| `PUSHER_APP_PORT` | `6001` |
| `PUSHER_SCHEME` | `http` |
| `VITE_PUSHER_APP_KEY` | same as `PUSHER_APP_KEY` |

## API Endpoints

| Method | Route | Action |
|---|---|---|
| GET | `/notifications` | Paginated notification list |
| POST | `/notifications/{id}/read` | Mark single notification read |
| POST | `/notifications/read-all` | Mark all notifications read |
| DELETE | `/notifications/{id}` | Delete a notification |

## Notification Payloads

| Notification | Data Keys |
|---|---|
| `BlogSynced` | `blog_id`, `blog_name`, `post_count`, `synced_at` |
| `BulkActionCompleted` | `action`, `total`, `succeeded`, `failed`, `bulk_operation_id` |
| `PostPublished` | `post_id`, `post_title`, `blog_name`, `published_at` |
| `SocialShareResult` | `post_id`, `platform`, `status`, `message` |

## Test Cases

- [ ] `BlogSynced` notification is stored in `notifications` table after `SyncBlogPostsJob` completes
- [ ] `BulkActionCompleted` notification is stored after `BulkPostActionJob` completes
- [ ] `PostPublished` notification is stored after `PublishPostJob` completes
- [ ] `SocialShareResult` notification is stored after share job completes (success and failure)
- [ ] Notification broadcast event is fired on `private-user.{id}` channel
- [ ] Unauthenticated user cannot subscribe to another user's private channel
- [ ] `POST /notifications/{id}/read` marks only the specified notification as read
- [ ] `POST /notifications/read-all` marks all unread notifications read for the user
- [ ] `NotificationBell` badge count equals unread notification count on page load
- [ ] Receiving a broadcast event increments badge count without page reload
- [ ] Clicking a notification navigates to the correct resource URL
