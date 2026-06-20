# Blogify — Multi-Tenant SaaS for Indian Blogger Management

Blogify lets Indian content creators connect their Google/Blogger accounts, manage posts and labels, generate AI content, and auto-share to social media — all from one dashboard.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13, PHP 8.3+ |
| Frontend | Inertia.js v2 + React 18 |
| Styling | Tailwind CSS 4 |
| Database | MySQL |
| Queue / Jobs | Laravel Horizon + Redis |
| WebSockets | Soketi |
| Billing | Razorpay |
| AI Providers | OpenAI, Anthropic Claude, Google Gemini (user-supplied keys) |

---

## Environment

| Key | Value |
|---|---|
| Project Path | `C:\herd\project\blogger-saas-v2` |
| OS | Windows + Laravel Herd |
| Local URL | `http://localhost:8000` |
| Run Command | `php artisan serve` |

> **⚠️ Google OAuth Warning:** Google OAuth rejects local TLDs like `.test`. Always use `http://localhost:8000` for development. Register `http://localhost:8000/auth/google/callback` as the authorised redirect URI in Google Cloud Console.

---

## Documentation Map

### Features

| # | Feature | File | Status |
|---|---|---|---|
| 1 | Authentication | [features/01-authentication/index.md](features/01-authentication/index.md) | ✅ Complete |
| 2 | Blogger Integration | [features/02-blogger-integration/index.md](features/02-blogger-integration/index.md) | ✅ Complete |
| 3 | Posts Management | [features/03-posts-management/index.md](features/03-posts-management/index.md) | ✅ Complete |
| 4 | Notifications | [features/04-notifications/index.md](features/04-notifications/index.md) | 🔜 Next |
| 5 | AI Providers | [features/05-ai-providers/index.md](features/05-ai-providers/index.md) | ⏳ Planned |
| 6 | Social Media | [features/06-social-media/index.md](features/06-social-media/index.md) | ⏳ Planned |
| 7 | Scheduling | [features/07-scheduling/index.md](features/07-scheduling/index.md) | ⏳ Planned |

### Modules

| Module | File |
|---|---|
| Auth | [modules/auth/index.md](modules/auth/index.md) |
| Blogger | [modules/blogger/index.md](modules/blogger/index.md) |
| Posts | [modules/posts/index.md](modules/posts/index.md) |
| Labels | [modules/labels/index.md](modules/labels/index.md) |
| Notifications | [modules/notifications/index.md](modules/notifications/index.md) |
| AI | [modules/ai/index.md](modules/ai/index.md) |
| Social | [modules/social/index.md](modules/social/index.md) |
| Scheduler | [modules/scheduler/index.md](modules/scheduler/index.md) |
| Billing | [modules/billing/index.md](modules/billing/index.md) |

---

## Phase Status

| Phase | Feature | Status |
|---|---|---|
| Phase 1 | Authentication (Google OAuth, token encryption, refresh) | ✅ Complete |
| Phase 2 | Blogger Integration (connect, sync, incremental updates) | ✅ Complete |
| Phase 3 | Posts Management (CRUD, labels, bulk ops, dashboard) | ✅ Complete |
| Phase 4 | Notifications (DB + broadcast via Soketi, bell UI) | 🔜 **Next** |
| Phase 5 | AI Providers (OpenAI / Claude / Gemini, encrypted keys) | ⏳ Planned |
| Phase 6 | Social Media (Facebook / Instagram / Twitter / LinkedIn) | ⏳ Planned |
| Phase 7 | Scheduling (cron publish, timezone-aware IST) | ⏳ Planned |

---

## Build Workflow

- **Prompts create files only.** No `composer`, `npm`, `artisan`, or `migrate` commands are run during documentation or scaffolding sessions. Install / migrate steps are listed separately inside each feature file under a *Setup* section.
- **Plan:** 11-week, 90+ micro-task roadmap; each phase maps to one or more feature files.
- **Test coverage target:** 55+ test cases across unit, feature, and browser tests.
- Migrations are written first; models second; services third; jobs fourth; controllers/routes last; React pages last.
