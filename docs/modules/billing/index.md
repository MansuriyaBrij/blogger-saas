# Billing Module

## Responsibility

Handles Razorpay subscription billing for Indian users — plan creation, subscription lifecycle, webhook verification, and feature gating based on the user's active plan.

## Classes

| Class | Role |
|---|---|
| `App\Services\RazorpayService` | Creates Razorpay orders/subscriptions; verifies webhook signatures; cancels subscriptions |
| `App\Http\Controllers\BillingController` | `plans` (pricing page), `subscribe`, `cancel`, `webhook` HTTP actions |
| `App\Models\Subscription` | Tracks the active Razorpay subscription per user |
| `App\Http\Middleware\CheckPlanLimit` | Gates feature access based on `users.plan` and configurable limits |

## Key Methods

| Method | Class | Description |
|---|---|---|
| `createSubscription(User, string $planId): array` | `RazorpayService` | Calls Razorpay API; returns subscription object |
| `cancelSubscription(string $subscriptionId): void` | `RazorpayService` | Cancels subscription at period end |
| `verifyWebhookSignature(Request): bool` | `RazorpayService` | HMAC-SHA256 verification using `RAZORPAY_WEBHOOK_SECRET` |
| `handlePaymentCaptured(array $payload): void` | `BillingController` | Activates subscription; updates `users.plan` |
| `handleSubscriptionCancelled(array $payload): void` | `BillingController` | Reverts user to `free` plan |
| `subscribe(Request): RedirectResponse` | `BillingController` | Creates Razorpay subscription and redirects to checkout |
| `webhook(Request): Response` | `BillingController` | Receives and routes Razorpay webhook events |

## Models

### Subscription

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | bigint | FK → users, cascade delete |
| `razorpay_subscription_id` | string | External ID from Razorpay |
| `razorpay_plan_id` | string | Razorpay plan ID |
| `status` | enum | `created`, `active`, `halted`, `cancelled` |
| `current_start` | timestamp | Current billing period start |
| `current_end` | timestamp | Current billing period end |
| `ends_at` | timestamp | Nullable; set on cancellation |
| `created_at` / `updated_at` | timestamps | — |

## Webhook Events Handled

| Event | Action |
|---|---|
| `payment.captured` | Activate subscription; set `users.plan` |
| `subscription.charged` | Renew `current_start` / `current_end` on `subscriptions` |
| `subscription.cancelled` | Set `subscriptions.ends_at`; revert `users.plan = 'free'` at period end |
| `subscription.halted` | Flag subscription as halted; notify user to update payment |

## Plan Feature Limits

| Plan | Blogs | AI Calls/Month | Social Accounts |
|---|---|---|---|
| `free` | 1 | 10 | 0 |
| `pro` | 5 | 500 | 3 |
| `agency` | Unlimited | Unlimited | Unlimited |

Feature limit checks are centralised in `CheckPlanLimit` middleware and a `PlanLimits` value object — no scattered `if ($user->plan === 'free')` checks in controllers.

## Notes

- Webhook endpoint must be excluded from CSRF middleware: add `/billing/webhook` to `VerifyCsrfToken::$except`.
- **Always verify the Razorpay webhook signature before processing** — never trust the payload without HMAC validation.
- Razorpay API keys (`RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`) live in `.env` only, never committed.
- All amounts are in **paise** (Indian Rupees × 100) when passed to the Razorpay API.
- `users.plan` is the source of truth for active access; `subscriptions` table is the audit trail.
- Grace period: if a charge fails, Razorpay retries per the plan config. Blogify should not downgrade the user until `subscription.cancelled` or `subscription.halted` is received.
