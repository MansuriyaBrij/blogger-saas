# Social Module

## Responsibility

Manages OAuth connections to social platforms and dispatches per-post share jobs. Each platform is encapsulated in its own connector; the manager selects the right connector at runtime and handles retry/failure logging.

## Classes

| Class | Role |
|---|---|
| `App\Services\Social\SocialShareManager` | Resolves connectors; dispatches `ShareToSocialJob` per platform |
| `App\Services\Social\Connectors\FacebookConnector` | Implements `SocialConnector` for Facebook Graph API |
| `App\Services\Social\Connectors\InstagramConnector` | Implements `SocialConnector` for Instagram Graph API |
| `App\Services\Social\Connectors\TwitterConnector` | Implements `SocialConnector` for Twitter v2 API |
| `App\Services\Social\Connectors\LinkedInConnector` | Implements `SocialConnector` for LinkedIn v2 API |
| `App\Jobs\ShareToSocialJob` | Resolves connector, generates caption via AI module, calls `connector->share()` |
| `App\Models\SocialAccount` | Stores one connected platform account per row |
| `App\Models\SocialShare` | Log of every share attempt with status and platform post ID |
| `App\Http\Controllers\SocialController` | OAuth redirect/callback + list/disconnect actions |

## Contract

```php
namespace App\Services\Social\Contracts;

interface SocialConnector
{
    /**
     * Share a post to the platform.
     *
     * @param  string  $content  Caption / body text (already trimmed to maxLength)
     * @param  string  $url      Public post URL to attach
     * @param  array   $options  Platform-specific extras (e.g. image_url, hashtags)
     * @return string            Platform-assigned post ID
     * @throws \App\Exceptions\SocialShareException
     */
    public function share(string $content, string $url, array $options = []): string;

    /** Platform slug: facebook | instagram | twitter | linkedin */
    public function platform(): string;

    /** Maximum caption character count for this platform */
    public function maxLength(): int;
}
```

## Models

### SocialAccount

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | bigint | FK → users, cascade delete |
| `platform` | enum | `facebook`, `instagram`, `twitter`, `linkedin` |
| `platform_user_id` | string | External account ID |
| `access_token` | text | Encrypted AES-256-CBC |
| `token_expires_at` | timestamp | Nullable |
| `display_name` | string | Account label shown in UI |

### SocialShare

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `post_id` | bigint | FK → posts |
| `social_account_id` | bigint | FK → social_accounts |
| `platform` | enum | Denormalised copy |
| `status` | enum | `pending`, `success`, `failed` |
| `platform_post_id` | string | Nullable; returned by platform on success |
| `caption` | text | Actual caption used |
| `error_message` | text | Nullable; last error message |
| `shared_at` | timestamp | Nullable; set on success |

## Notes

- Access tokens are encrypted at rest with `encrypt()` and listed in `SocialAccount::$hidden`.
- `ShareToSocialJob` runs in the `social` Horizon queue with `$tries = 3` and exponential backoff.
- Caption generation via `AiProviderManager::generate()` is optional — if no AI credential is configured, a default caption template is used.
- Caption is truncated to `connector->maxLength()` before being stored in `social_shares.caption` and sent to the API.
- Platform OAuth flows require their own app registrations separate from Google.
