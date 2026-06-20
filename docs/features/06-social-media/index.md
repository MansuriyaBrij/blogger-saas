# Phase 6 — Social Media ⏳

## Overview

After a post is published on Blogger, Blogify can auto-share it to connected social media accounts. A `SocialShareManager` dispatches platform-specific jobs; each platform has its own connector implementing a common `SocialConnector` interface. AI-generated captions (from Phase 5) are optionally attached per platform. Each share attempt is logged in `social_shares` so failures can be retried.

## Capabilities

- Connect Facebook Page, Instagram Business, Twitter/X, and LinkedIn accounts via OAuth.
- Auto-share on publish or manually share any post to selected platforms.
- AI-generated captions per platform (character limits respected: Twitter 280, LinkedIn 3000).
- Per-platform retry logic (up to 3 attempts) with exponential backoff in Horizon.
- Share history per post showing platform, status, timestamp, and error if any.
- Disconnect a social account without affecting existing share history.

## Components

| Type | Name |
|---|---|
| Manager | `App\Services\Social\SocialShareManager` |
| Connector | `App\Services\Social\Connectors\FacebookConnector` |
| Connector | `App\Services\Social\Connectors\InstagramConnector` |
| Connector | `App\Services\Social\Connectors\TwitterConnector` |
| Connector | `App\Services\Social\Connectors\LinkedInConnector` |
| Job | `App\Jobs\ShareToSocialJob` |
| Model | `App\Models\SocialAccount` |
| Model | `App\Models\SocialShare` |
| Controller | `App\Http\Controllers\SocialController` |
| React Page | `resources/js/pages/Social/Index.jsx` |
| React Page | `resources/js/pages/Social/Connect.jsx` |

## SocialConnector Interface

```php
namespace App\Services\Social\Contracts;

interface SocialConnector
{
    /**
     * Post content to the platform.
     * @throws \App\Exceptions\SocialShareException on failure.
     */
    public function share(string $content, string $url, array $options = []): string;

    /**
     * Return the platform slug (facebook, instagram, twitter, linkedin).
     */
    public function platform(): string;

    /**
     * Maximum caption length for this platform.
     */
    public function maxLength(): int;
}
```

## DB Fields (social_accounts)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | bigint | FK → users |
| `platform` | enum | `facebook`, `instagram`, `twitter`, `linkedin` |
| `platform_user_id` | string | Platform-specific account ID |
| `access_token` | text | Encrypted AES-256-CBC |
| `token_expires_at` | timestamp | Nullable; platforms vary |
| `display_name` | string | Human-readable account label |
| `created_at` / `updated_at` | timestamps | — |

## DB Fields (social_shares)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `post_id` | bigint | FK → posts |
| `social_account_id` | bigint | FK → social_accounts |
| `platform` | enum | Denormalised for fast queries |
| `status` | enum | `pending`, `success`, `failed` |
| `platform_post_id` | string | Nullable; ID returned by platform API |
| `caption` | text | Caption used (may be AI-generated) |
| `error_message` | text | Nullable; populated on failure |
| `shared_at` | timestamp | Nullable; set on success |
| `created_at` / `updated_at` | timestamps | — |

## Share Flow

1. Post is published (manually or via scheduler).
2. `SocialShareManager::sharePost($post, $platforms[])` is called.
3. For each platform, `ShareToSocialJob` is dispatched to the `social` Horizon queue.
4. Job resolves the correct connector, optionally calls `AiProviderManager::generate()` for a caption, then calls `connector->share()`.
5. On success: `social_shares.status = 'success'`, `platform_post_id` stored, `SocialShareResult` notification fired.
6. On failure: `social_shares.status = 'failed'`, `error_message` stored, job retried up to 3× with backoff.
7. After final failure: `SocialShareResult` notification fired with `status = 'failed'`.

## Platform Caption Limits

| Platform | Max Characters |
|---|---|
| Twitter / X | 280 |
| LinkedIn | 3 000 |
| Facebook | 63 206 |
| Instagram | 2 200 |

## Security Notes

- Social platform access tokens are encrypted at rest (`encrypt()`).
- OAuth callbacks for social platforms are scoped with CSRF state parameters.
- Users can only share to their own connected social accounts — ownership is verified before dispatch.

## Test Cases

- [ ] `POST /social/connect/facebook` redirects to Facebook OAuth
- [ ] Facebook callback stores encrypted token in `social_accounts`
- [ ] `DELETE /social/{id}` disconnects account and does not delete share history
- [ ] `ShareToSocialJob` calls `connector->share()` with correct content and URL
- [ ] Successful share sets `social_shares.status = 'success'` and stores `platform_post_id`
- [ ] Failed share sets `social_shares.status = 'failed'` and stores `error_message`
- [ ] Job is retried up to 3 times on failure
- [ ] `SocialShareResult` notification is dispatched after success
- [ ] `SocialShareResult` notification is dispatched after final failure
- [ ] AI caption is truncated to platform max length before sharing
- [ ] User cannot share to another user's social account (403)
