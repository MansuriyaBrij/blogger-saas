# Blogger SaaS Platform — Full Project Plan

> **Stack:** Laravel 11 · Inertia.js (React 18) · Tailwind CSS 4 · Redis · Soketi · Razorpay  
> **Timeline:** 8 Weeks · 6 Phases · 60+ Micro Tasks · 40+ Test Cases

---

## Project Overview

Multi-tenant SaaS platform where users connect their Google/Blogger account, manage all their blogs, posts, categories (labels), and tags from one dashboard. Includes bulk operations, AI content generation, CSV import, real-time notifications, and subscription billing.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 11 |
| Frontend | Inertia.js + React 18 |
| Styling | Tailwind CSS 4 |
| Database | MySQL 8 |
| Cache | Redis |
| Queue | Laravel Queue (Redis driver) + Horizon |
| Real-time | Laravel Echo + Soketi (self-hosted) |
| Auth | Laravel Socialite (Google OAuth) |
| Blogger API | google/apiclient |
| AI | Anthropic Claude API |
| Excel/CSV | maatwebsite/excel |
| Payment | Razorpay (India) |
| Email | Mailgun + Laravel Mail |
| Monitoring | Laravel Telescope + Horizon |
| Error tracking | Sentry (Laravel + React) |
| Deploy | Hetzner VPS + Ploi |
| Storage | Laravel Storage (S3 / local) |

---

## Database Schema

```sql
-- Core user table
users
  id, name, email, google_id
  google_access_token (encrypted)
  google_refresh_token (encrypted)
  google_token_expires_at
  plan (free | pro | agency)
  created_at, updated_at

-- Connected blogger blogs
blogger_accounts
  id, user_id, blog_id, blog_name, blog_url
  is_active, last_synced_at
  created_at

-- Local post cache (synced from Blogger API)
posts
  id, user_id, blog_id (FK blogger_accounts)
  blogger_post_id, title, content, url
  labels (JSON), status (LIVE|DRAFT|SCHEDULED)
  published_at, synced_at
  created_at, updated_at

-- Labels / categories / tags
labels
  id, user_id, blog_id, name, post_count
  created_at, updated_at

-- Bulk operation tracking
bulk_operations
  id, user_id, blog_id, type
  total, success, failed, status (pending|running|done|failed)
  error_log (JSON)
  created_at, completed_at

-- CSV/bulk import jobs
import_jobs
  id, user_id, blog_id
  file_path, total, processed, success, failed
  status, error_log (JSON)
  created_at, completed_at

-- Notifications
notifications
  id, user_id, type, data (JSON)
  read_at, created_at

-- Notification preferences
notification_preferences
  id, user_id, type, email_enabled, inapp_enabled

-- Subscriptions / billing
subscriptions
  id, user_id, plan, razorpay_subscription_id
  razorpay_plan_id, status
  starts_at, ends_at, cancelled_at
  created_at, updated_at

-- AI usage tracking
ai_usage_logs
  id, user_id, tokens_used, request_type
  billing_period (YYYY-MM)
  created_at
```

---

## SaaS Plans

| Feature | Free | Pro ₹399/mo | Agency ₹999/mo |
|---------|------|-------------|----------------|
| Blogs | 1 | 3 | Unlimited |
| Posts/month | 10 | 500 | Unlimited |
| Bulk operations | ❌ | ✅ | ✅ |
| AI Generate | ❌ | 50/mo | Unlimited |
| CSV Import | ❌ | ✅ | ✅ |
| Label Manager | Basic | Full | Full |
| Analytics | ❌ | ✅ | ✅ |
| Email notifications | ❌ | ✅ | ✅ |
| Real-time updates | ❌ | ✅ | ✅ |
| Priority support | ❌ | ❌ | ✅ |

---

## Phase 1 — Project Setup + Google OAuth

**Week 1 · 10 Tasks**

### Tech
- Laravel 11 install
- Inertia.js + React 18 SSR setup
- Tailwind CSS 4
- Laravel Socialite (Google)

### Backend Micro Tasks
- [ ] `laravel new blogger-saas` — Inertia SSR + Tailwind config
- [ ] Google Cloud Console — project create, Blogger API v3 enable, OAuth 2.0 credentials
- [ ] Install `laravel/socialite`, configure Google provider
- [ ] OAuth scopes: `blogger` + `https://www.googleapis.com/auth/userinfo.email`
- [ ] DB migrations: users, blogger_accounts, subscriptions, notifications
- [ ] Routes: `/auth/google/redirect`, `/auth/google/callback`, `/logout`
- [ ] Encrypt + store `google_access_token` and `google_refresh_token` in DB
- [ ] `RefreshGoogleTokenJob` — auto-refresh expired tokens (scheduled every hour)

### Frontend Micro Tasks (React + Inertia)
- [ ] Landing page — Hero, "Connect with Google" CTA button
- [ ] Auth layout — Inertia shared layout, user state via Inertia's `usePage()`
- [ ] Google OAuth button component — loading state, error handling

### Test Cases
| Type | Test |
|------|------|
| Unit · AuthController | Google callback creates new user if not exists |
| Unit · AuthController | Google callback updates token if user already exists |
| Feature · OAuth | Redirect URL contains correct scopes |
| Feature · Token | Expired token auto-refreshes before API call |
| Feature · Auth | Unauthenticated user redirected to login page |

---

## Phase 2 — Blogger API Integration + Blog Connect

**Week 2 · 12 Tasks**

### Tech
- `google/apiclient` PHP package
- Redis cache
- Laravel Queue (database driver initially)
- Tanstack Query (React)

### Blogger API Endpoints
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/blogger/v3/users/self/blogs` | List all user blogs |
| GET | `/blogger/v3/blogs/{blogId}` | Blog details |
| GET | `/blogger/v3/blogs/{blogId}/posts` | List posts |
| GET | `/blogger/v3/blogs/{blogId}/posts/{postId}` | Single post |

### Backend Micro Tasks
- [ ] `BloggerService` class — wrapper for `google/apiclient`, auto token inject, retry on 401
- [ ] `GET /api/blogs` — fetch from Google, store in `blogger_accounts`, return to frontend
- [ ] `SyncBlogPostsJob` — fetch all posts → save to local `posts` table (queued)
- [ ] Label extraction — parse labels from posts → populate `labels` table with post_count
- [ ] Cache strategy — blog list: 1hr TTL, posts: 30min TTL, auto-invalidate on write ops
- [ ] Rate limit handler — Blogger API 10,000 units/day — track usage, throttle queue

### Frontend Micro Tasks
- [ ] Blog connect page — show all Google blogs as cards, "Connect" button per blog
- [ ] Blog switcher dropdown — sidebar/navbar, stores selected `blog_id` in session/Inertia shared
- [ ] Sync status UI — progress indicator via Soketi broadcast while `SyncBlogPostsJob` runs
- [ ] Dashboard skeleton — stat cards: total posts, labels, drafts, published count

### Test Cases
| Type | Test |
|------|------|
| Unit · BloggerService | Returns blog list with valid token |
| Unit · BloggerService | Throws + triggers token refresh on 401 |
| Feature · Sync | SyncBlogPostsJob saves correct post count to DB |
| Feature · Labels | Labels table populated correctly after sync |
| Feature · Cache | Second blog list request served from cache, no API call |
| Feature · Multi-blog | User with 3 blogs sees all 3 in blog switcher |

---

## Phase 3 — Posts + Labels Management

**Week 3–4 · 14 Tasks**

### Blogger API Endpoints
| Method | Endpoint | Purpose |
|--------|----------|---------|
| PUT | `/blogger/v3/blogs/{blogId}/posts/{postId}` | Update post |
| DELETE | `/blogger/v3/blogs/{blogId}/posts/{postId}` | Delete post |
| POST | `/blogger/v3/blogs/{blogId}/posts/{postId}/publish` | Publish draft |
| POST | `/blogger/v3/blogs/{blogId}/posts/{postId}/revert` | Revert to draft |

### Backend Micro Tasks — Posts
- [ ] `GET /api/posts` — paginate, search by title, filter by status/label/date range
- [ ] `GET /api/posts/{id}` — single post with full content
- [ ] `PUT /api/posts/{id}` — update title/content/labels, sync to Blogger API
- [ ] `POST /api/posts/{id}/toggle-status` — publish draft / revert to draft
- [ ] `POST /api/posts/bulk` — dispatch `BulkPublishJob` / `BulkDraftJob` / `BulkDeleteJob` / `BulkLabelJob`
- [ ] `bulk_operations` table tracking — total / success / failed per bulk job batch

### Backend Micro Tasks — Labels
- [ ] `GET /api/labels` — all labels with post count, sorted by usage
- [ ] `PUT /api/labels/{id}/rename` — update label on all posts in Blogger API + local DB (queued)
- [ ] `POST /api/labels/merge` — merge label A → B on all posts, delete A from labels table
- [ ] `DELETE /api/labels/{id}` — remove label from all posts in Blogger API (queued)

### Frontend Micro Tasks
- [ ] Posts data table — checkbox select, inline status badge, sortable columns, pagination
- [ ] Bulk action bar — appears on selection: Publish / Draft / Delete / Add Label / Remove Label
- [ ] Labels manager page — cards with count, rename modal, merge modal, delete confirm dialog
- [ ] Filter sidebar — status, label multi-select, date range — synced to URL query params

### Test Cases
| Type | Test |
|------|------|
| Unit · PostController | Search returns only posts matching title keyword |
| Unit · LabelService | Rename updates all posts with old label in DB |
| Unit · LabelService | Merge combines post_count correctly, removes old label |
| Feature · Bulk | Bulk publish dispatches correct number of queue jobs |
| Feature · Bulk | Failed Blogger API call marks specific job as failed in bulk_operations |
| Feature · Policy | User cannot edit/view posts from another user's blog (authorization) |
| Browser · React | Selecting 5 posts shows bulk action bar with correct count |

---

## Phase 4 — Notifications System

**Week 5 · 10 Tasks**

### Tech
- Laravel Echo + Soketi (self-hosted, Pusher-compatible)
- Mailgun + Laravel Mail
- Redis queue driver
- `laravel-echo` npm package (React side)

### Notification Types + Triggers

| Event | Channel | Trigger |
|-------|---------|---------|
| `BulkOperationCompleted` | In-app + Email | Bulk job queue finishes |
| `BulkOperationFailed` | In-app + Email | Any job in bulk fails |
| `PostScheduled` | In-app | Scheduled post goes live |
| `ImportComplete` | In-app + Email | CSV import job done |
| `ApiQuotaWarning` | In-app | 80% of daily Blogger API quota used |
| `SyncComplete` | In-app | Blog sync from Blogger finished |

### Micro Tasks
- [ ] Soketi server setup — self-hosted, .env config for Echo
- [ ] `notifications` table + model — `markAsRead()`, `unreadCount()` scope
- [ ] Mailable classes — one per notification type, Blade email templates
- [ ] Bell icon component (React) — dropdown with unread count badge, "mark all read"
- [ ] Echo listener (React) — listen on private channel `user.{id}`, auto-append new notifs
- [ ] Notification preferences page — per-user toggle: email on/off per notification type
- [ ] Notification center page — paginated list, filter by type, mark read/unread

### Test Cases
| Type | Test |
|------|------|
| Unit · Notification | `BulkOperationCompleted` stores correct JSON data in DB |
| Unit · Mail | Mailable renders correct subject line and body content |
| Feature · Broadcast | Event broadcasts on private channel `user.{id}` |
| Feature · Preferences | User with email disabled does not receive email notification |
| Feature · API | Mark-all-read sets `read_at` on all user's notifications |

---

## Phase 5 — AI Content Generation + Bulk CSV Import

**Week 6 · 10 Tasks**

### Tech
- Anthropic Claude API
- `maatwebsite/excel` (CSV/Excel parsing)
- Laravel Storage (S3)
- Laravel Horizon (queue monitoring)

### Backend Micro Tasks — AI Generate
- [ ] `ClaudeService` class — API wrapper, SEO blog writing system prompt, error handling
- [ ] `POST /api/ai/generate` — topic input → Claude API → return HTML content + meta title + meta desc
- [ ] `POST /api/ai/bulk-generate` — CSV with topics → `GenerateAndPublishJob` per row → Blogger publish
- [ ] AI usage tracking — store tokens used per user per month in `ai_usage_logs`

### Backend Micro Tasks — CSV Import
- [ ] Import validator — validate CSV columns (title, content, labels, status, publish_date), plan limits
- [ ] `ImportBlogPostsJob` — per-row job: parse → publish to Blogger API → update `import_jobs` record
- [ ] Import history API — list all imports with date, total/success/failed, download error log CSV

### Frontend Micro Tasks
- [ ] AI generate page (React) — topic input, tone selector, language selector, preview panel, publish button
- [ ] Drag-drop CSV upload — column mapping step, row preview, confirm + submit
- [ ] Import progress (React) — real-time % bar via Soketi broadcast as jobs complete
- [ ] Import history page — table of past imports, expandable error log

### Test Cases
| Type | Test |
|------|------|
| Unit · ClaudeService | Returns structured content with title, body, meta fields |
| Unit · ImportValidator | Rejects CSV missing required columns |
| Feature · Import | 10-row CSV creates 10 queue jobs |
| Feature · Import | Failed row logged without stopping other rows |
| Feature · Plan limit | Free user blocked from importing more than 10 posts |
| Feature · AI usage | Token count stored correctly per request |

---

## Phase 6 — SaaS Billing + Plan Limits + Deploy

**Week 7–8 · 12 Tasks**

### Tech
- Razorpay Subscriptions API
- `CheckPlanLimit` middleware
- Hetzner VPS + Ploi
- Laravel Horizon + Telescope

### Micro Tasks
- [ ] `CheckPlanLimit` middleware — gates features by user's plan (post count, blog count, AI access)
- [ ] Razorpay subscription create — plan selection → checkout → subscription record in DB
- [ ] Razorpay webhook handler — `payment.captured` → upgrade plan, `subscription.cancelled` → downgrade
- [ ] Billing page (React) — current plan display, upgrade CTA, invoice history, cancel subscription
- [ ] Usage dashboard — posts this month, AI tokens used, API quota % bar
- [ ] Settings page — notification preferences, connected blogs manage, account delete
- [ ] Horizon setup — queue monitoring, failed job retry, queue priority (bulk vs single)
- [ ] Hetzner VPS deploy — Ploi server config, SSL, Redis, Soketi, Horizon supervisor
- [ ] Sentry integration — error tracking for Laravel backend + React frontend

### Test Cases
| Type | Test |
|------|------|
| Feature · Plan gate | Free user blocked from bulk operations (402 response) |
| Feature · Razorpay | `payment.captured` webhook upgrades user to Pro |
| Feature · Razorpay | `subscription.cancelled` webhook downgrades user to Free |
| Feature · Usage | Post count resets at start of each billing period |
| Security | User cannot access another user's blog data (authorization) |
| Security | Webhook signature verification rejects invalid/tampered requests |

---

## Project Folder Structure

```
blogger-saas/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── BlogController.php
│   │   │   ├── PostController.php
│   │   │   ├── LabelController.php
│   │   │   ├── BulkController.php
│   │   │   ├── ImportController.php
│   │   │   ├── AiController.php
│   │   │   ├── NotificationController.php
│   │   │   └── BillingController.php
│   │   └── Middleware/
│   │       └── CheckPlanLimit.php
│   ├── Services/
│   │   ├── BloggerService.php
│   │   └── ClaudeService.php
│   ├── Jobs/
│   │   ├── SyncBlogPostsJob.php
│   │   ├── BulkPublishJob.php
│   │   ├── BulkDraftJob.php
│   │   ├── BulkDeleteJob.php
│   │   ├── BulkLabelJob.php
│   │   ├── ImportBlogPostsJob.php
│   │   ├── GenerateAndPublishJob.php
│   │   └── RefreshGoogleTokenJob.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── BloggerAccount.php
│   │   ├── Post.php
│   │   ├── Label.php
│   │   ├── BulkOperation.php
│   │   ├── ImportJob.php
│   │   ├── Notification.php
│   │   └── Subscription.php
│   ├── Notifications/
│   │   ├── BulkOperationCompleted.php
│   │   ├── BulkOperationFailed.php
│   │   ├── ImportComplete.php
│   │   └── ApiQuotaWarning.php
│   └── Policies/
│       ├── PostPolicy.php
│       └── BlogPolicy.php
├── resources/
│   └── js/
│       ├── Pages/
│       │   ├── Auth/Login.jsx
│       │   ├── Dashboard.jsx
│       │   ├── Posts/Index.jsx
│       │   ├── Posts/Edit.jsx
│       │   ├── Labels/Index.jsx
│       │   ├── Import/Index.jsx
│       │   ├── Import/History.jsx
│       │   ├── AI/Generate.jsx
│       │   ├── Notifications/Index.jsx
│       │   └── Billing/Index.jsx
│       ├── Components/
│       │   ├── PostsTable.jsx
│       │   ├── BulkActionBar.jsx
│       │   ├── LabelCard.jsx
│       │   ├── BlogSwitcher.jsx
│       │   ├── BellNotification.jsx
│       │   ├── FilterSidebar.jsx
│       │   └── ImportProgress.jsx
│       └── Layouts/
│           └── AppLayout.jsx
├── tests/
│   ├── Unit/
│   │   ├── AuthControllerTest.php
│   │   ├── BloggerServiceTest.php
│   │   ├── LabelServiceTest.php
│   │   ├── ClaudeServiceTest.php
│   │   └── ImportValidatorTest.php
│   └── Feature/
│       ├── OAuthTest.php
│       ├── BlogSyncTest.php
│       ├── PostManagementTest.php
│       ├── BulkOperationsTest.php
│       ├── NotificationsTest.php
│       ├── ImportTest.php
│       ├── PlanLimitsTest.php
│       └── RazorpayWebhookTest.php
└── PROJECT_PLAN.md
```

---

## Key Packages

```json
{
  "require": {
    "laravel/socialite": "^5.x",
    "google/apiclient": "^2.x",
    "maatwebsite/excel": "^3.x",
    "laravel/horizon": "^5.x",
    "laravel/telescope": "^5.x"
  },
  "require-dev": {
    "pestphp/pest": "^3.x",
    "pestphp/pest-plugin-laravel": "^3.x"
  }
}
```

```json
{
  "dependencies": {
    "@inertiajs/react": "^2.x",
    "react": "^18.x",
    "laravel-echo": "^2.x",
    "pusher-js": "^8.x",
    "@tanstack/react-query": "^5.x"
  }
}
```

---

## Timeline Summary

| Phase | Description | Week | Tasks |
|-------|-------------|------|-------|
| 1 | Google OAuth + Setup | 1 | 10 |
| 2 | Blogger API + Blog Connect | 2 | 12 |
| 3 | Posts + Labels Management | 3–4 | 14 |
| 4 | Notifications System | 5 | 10 |
| 5 | AI Generate + CSV Import | 6 | 10 |
| 6 | Billing + Deploy | 7–8 | 12 |
| **Total** | | **8 weeks** | **68 tasks** |

---

*Generated for: Blogger SaaS Platform · Laravel 11 + Inertia.js (React)*
