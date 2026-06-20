# Notifications Module

## Responsibility

Delivers real-time and persisted in-app notifications to users for all background-job outcomes. Uses Laravel's database and broadcast notification channels together — one dispatch writes to DB and pushes over WebSocket simultaneously.

## Classes

| Class | Role |
|---|---|
| `App\Notifications\BlogSynced` | Fired after `SyncBlogPostsJob` completes |
| `App\Notifications\BulkActionCompleted` | Fired after `BulkPostActionJob` finishes all items |
| `App\Notifications\PostPublished` | Fired after `PublishPostJob` or `PublishScheduledPostJob` succeeds |
| `App\Notifications\SocialShareResult` | Fired after `ShareToSocialJob` succeeds or exhausts retries |
| `App\Http\Controllers\NotificationController` | `index`, `markRead`, `markAllRead`, `destroy` |
| `App\Events\SyncCompleted` | Broadcastable event wrapping the `BlogSynced` notification payload |

## Contract

All notification classes follow this structure:

```php
public function via(object $notifiable): array
{
    return ['database', 'broadcast'];
}

public function toDatabase(object $notifiable): array
{
    return [
        'type'    => 'blog_synced',    // snake_case type key
        'title'   => 'Blog synced',
        'body'    => '…',
        'url'     => '/blogs',         // resource URL for click-through
        'data'    => [],               // extra payload
    ];
}

public function toBroadcast(object $notifiable): BroadcastMessage
{
    return new BroadcastMessage($this->toDatabase($notifiable));
}
```

## Broadcast Channel

```php
// routes/channels.php
Broadcast::channel('private-user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

## Frontend Hook

`useNotifications` (React):
- Subscribes to `private-user.{id}` via `laravel-echo` on mount.
- Listens for `Illuminate\Notifications\Events\BroadcastNotificationCreated`.
- Maintains `notifications` array and `unreadCount` in React state.
- Exposes `markRead(id)` and `markAllRead()` functions that POST to the API and update local state optimistically.

## Notes

- WebSocket driver: Soketi (`BROADCAST_DRIVER=pusher` with Soketi host/port).
- Laravel Echo must be initialised with `forceTLS: false` for local Soketi (HTTP, not HTTPS).
- `notifications` table is created by Laravel's built-in `php artisan notifications:table` migration — do not customise the schema.
- Unread count badge: `notifications` table `read_at IS NULL` count, queried on page load and incremented via WebSocket without a DB round trip.
- Notifications older than 30 days should be pruned via a scheduled `DB::table('notifications')->whereNotNull('read_at')->where('created_at', '<', now()->subDays(30))->delete()` command.
