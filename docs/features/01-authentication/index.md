# Phase 1 — Authentication ✅

## Overview

Blogify uses Google OAuth 2.0 as its sole login mechanism — no passwords. On first login the user grants Blogger API scopes; access and refresh tokens are encrypted at rest using Laravel's AES-256-CBC Crypt facade. The refresh token is rotated silently before each API call to ensure sessions never expire mid-use.

## User Flow

1. User visits `/login` and clicks **Continue with Google**.
2. App redirects to Google with scopes `openid email profile https://www.googleapis.com/auth/blogger`.
3. Google redirects to `/auth/google/callback` with an auth code.
4. `GoogleOAuthService` exchanges the code for `access_token` + `refresh_token`.
5. Both tokens are encrypted via `encrypt()` and stored on the `users` table.
6. User is authenticated via `Auth::login()` and redirected to `/dashboard`.
7. On every subsequent API call, `EnsureGoogleConnected` middleware checks token expiry and refreshes silently if needed.
8. Logout invalidates the session and regenerates the CSRF token.

## Components

| Type | Name |
|---|---|
| Service | `App\Services\GoogleOAuthService` |
| Controller | `App\Http\Controllers\Auth\GoogleController` |
| Middleware | `App\Http\Middleware\EnsureGoogleConnected` |
| Model | `App\Models\User` |
| React Page | `resources/js/pages/auth/Login.jsx` |
| Route | `GET /auth/google/redirect`, `GET /auth/google/callback` |

## OAuth Scopes

| Scope | Purpose |
|---|---|
| `openid` | Identity token |
| `email` | User email for account lookup |
| `profile` | Display name and avatar |
| `https://www.googleapis.com/auth/blogger` | Read/write access to Blogger API |

## Token Encryption

- Tokens are stored with `encrypt($token)` using Laravel's `Crypt` facade (AES-256-CBC + HMAC-SHA256).
- `APP_KEY` rotation requires re-authentication — if `DecryptException` is thrown, the middleware clears stale tokens and redirects to Google OAuth.
- Tokens are listed in `$hidden` on the `User` model and never serialised to JSON responses.

## DB Fields (users table)

| Column | Type | Notes |
|---|---|---|
| `google_id` | `string` | Google sub identifier, unique |
| `google_access_token` | `text` | Encrypted AES-256-CBC |
| `google_refresh_token` | `text` | Encrypted AES-256-CBC |
| `google_token_expires_at` | `timestamp` | UTC; compared via `->isPast()` |

## Security Notes

- `APP_KEY` must never be rotated without re-authenticating all users or re-encrypting stored tokens.
- The OAuth redirect URI must be an exact match in Google Cloud Console. For local dev: `http://localhost:8000/auth/google/callback`. `.test` TLDs are rejected by Google.
- CSRF is verified on every POST via Laravel's `PreventRequestForgery` middleware.
- Refresh tokens are only returned by Google on the *first* consent screen — store them immediately.

## Test Cases

- [ ] Unauthenticated user visiting `/dashboard` is redirected to `/login`
- [ ] Clicking "Continue with Google" redirects to `accounts.google.com`
- [ ] Callback with valid code creates a new user and logs them in
- [ ] Callback with valid code for existing user updates tokens, does not create duplicate
- [ ] `google_access_token` is stored encrypted (not plain text) in DB
- [ ] `google_refresh_token` is stored encrypted in DB
- [ ] Expired token triggers silent refresh before API call
- [ ] `DecryptException` on token read clears tokens and redirects to Google
- [ ] Logout invalidates session and redirects to `/`
- [ ] Visiting `/auth/google/redirect` while already authenticated redirects to dashboard
