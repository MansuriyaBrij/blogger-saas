# Auth Module

## Responsibility

Handles all aspects of user identity: Google OAuth 2.0 login, encrypted token storage, silent token refresh, and session management. No password-based auth is provided.

## Classes

| Class | Role |
|---|---|
| `App\Services\GoogleOAuthService` | Exchanges OAuth code for tokens; refreshes expired access tokens via Google token endpoint |
| `App\Http\Controllers\Auth\GoogleController` | HTTP layer: `redirectToGoogle()` and `handleGoogleCallback()` actions |
| `App\Http\Middleware\EnsureGoogleConnected` | Checks `google_access_token` presence and expiry before every protected API call; redirects to re-auth if tokens are stale or undecryptable |
| `App\Models\User` | Eloquent model; owns `google_*` columns; tokens listed in `$hidden` |

## Key Methods

| Method | Class | Description |
|---|---|---|
| `redirectToGoogle()` | `GoogleController` | Builds scoped OAuth URL and redirects |
| `handleGoogleCallback()` | `GoogleController` | Validates callback, upserts user, encrypts tokens, logs in |
| `refreshToken(User $user)` | `GoogleOAuthService` | POSTs to `https://oauth2.googleapis.com/token` with refresh token; updates `google_access_token` and `google_token_expires_at` |
| `handle(Request, Closure)` | `EnsureGoogleConnected` | Catches `DecryptException`, clears stale tokens, redirects to OAuth |

## Models

### User

| Column | Type | Notes |
|---|---|---|
| `google_id` | string | Unique; Google `sub` claim |
| `google_access_token` | text | `encrypt()`-ed; never returned in JSON |
| `google_refresh_token` | text | `encrypt()`-ed; never returned in JSON |
| `google_token_expires_at` | timestamp | UTC; cast to `datetime` |
| `plan` | enum | `free`, `pro`, `agency`; gates feature limits |

## Notes

- Token encryption uses `Illuminate\Support\Facades\Crypt` (AES-256-CBC + HMAC-SHA256 MAC).
- `DecryptException` means `APP_KEY` was rotated — clear tokens and force re-auth rather than crashing with 500.
- Google only returns a `refresh_token` on the first consent grant. If the user revokes and reconnects, the callback must handle a missing `refreshToken` gracefully.
- OAuth redirect URI registered in Google Cloud Console must exactly match `APP_URL . '/auth/google/callback'`. For local dev: `http://localhost:8000/auth/google/callback`.
