# Phase 5 — AI Providers ⏳

## Overview

Blogify lets each user supply their own API keys for OpenAI, Anthropic Claude, and Google Gemini. Keys are encrypted at rest; a driver pattern (`AiProviderManager` + per-provider drivers) abstracts all model calls so the rest of the app calls a single `generate()` method regardless of provider. Users pick a default provider and model per account from the Settings page.

## Capabilities

- Store, validate, and encrypt API keys per provider per user.
- Select a default provider and model (e.g. `gpt-4o`, `claude-sonnet-4-6`, `gemini-1.5-pro`).
- Generate blog post titles, outlines, full drafts, and SEO meta from existing content.
- Generate social media captions (used by the Social module).
- Key validation on save — a lightweight test call confirms the key works before storing.
- Graceful fallback: if the default provider fails, surface the error rather than silently switching.

## Components

| Type | Name |
|---|---|
| Manager | `App\Services\Ai\AiProviderManager` |
| Driver | `App\Services\Ai\Drivers\OpenAiDriver` |
| Driver | `App\Services\Ai\Drivers\AnthropicDriver` |
| Driver | `App\Services\Ai\Drivers\GeminiDriver` |
| Model | `App\Models\AiCredential` |
| Controller | `App\Http\Controllers\Settings\AiProviderController` |
| React Page | `resources/js/pages/Settings/AiProviders.jsx` |

## AiDriver Interface

```php
namespace App\Services\Ai\Contracts;

interface AiDriver
{
    /**
     * Send a prompt and return the generated text.
     */
    public function generate(string $prompt, array $options = []): string;

    /**
     * Validate that the stored API key can reach the provider.
     * Throws \App\Exceptions\AiKeyInvalidException on failure.
     */
    public function validateKey(): void;

    /**
     * Return the provider slug used in the DB and config.
     */
    public function provider(): string;
}
```

## DB Fields (ai_credentials)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | bigint | FK → users |
| `provider` | enum | `openai`, `anthropic`, `gemini` |
| `api_key` | text | Encrypted AES-256-CBC |
| `default_model` | string | e.g. `gpt-4o`, `claude-sonnet-4-6` |
| `is_default` | boolean | Only one row per user can be default |
| `created_at` / `updated_at` | timestamps | — |

## Provider / Model Reference

| Provider | Slug | Example Models |
|---|---|---|
| OpenAI | `openai` | `gpt-4o`, `gpt-4o-mini`, `gpt-3.5-turbo` |
| Anthropic | `anthropic` | `claude-sonnet-4-6`, `claude-haiku-4-5-20251001` |
| Google | `gemini` | `gemini-1.5-pro`, `gemini-1.5-flash` |

## Settings Page Flow

1. User opens **Settings → AI Providers**.
2. Enters API key + selects model for a provider.
3. `POST /settings/ai-providers` — controller calls `driver->validateKey()`.
4. If valid: key is encrypted and saved; success toast shown.
5. If invalid: `AiKeyInvalidException` caught, error returned to frontend.
6. User toggles **Set as Default** — sets `is_default = true` for that row, false for others.

## Security Notes

- API keys are encrypted with `encrypt()` (AES-256-CBC) and listed in `$hidden` on `AiCredential`.
- Keys are never returned to the frontend after being stored — the Settings page shows only the last 4 characters masked.
- Each user's keys are completely isolated — no shared pool keys unless user explicitly configures them.

## Test Cases

- [ ] Saving a valid OpenAI key stores it encrypted and returns success
- [ ] Saving an invalid OpenAI key returns a validation error, nothing is stored
- [ ] Saving a valid Anthropic key stores it encrypted and returns success
- [ ] Saving a valid Gemini key stores it encrypted and returns success
- [ ] `AiProviderManager::driver('openai')` returns an `OpenAiDriver` instance
- [ ] `generate()` on `OpenAiDriver` returns a non-empty string
- [ ] `generate()` on `AnthropicDriver` returns a non-empty string
- [ ] `generate()` on `GeminiDriver` returns a non-empty string
- [ ] Setting a provider as default flips `is_default` correctly (only one default per user)
- [ ] API key is not returned in full in any JSON response
- [ ] User cannot read or modify another user's `AiCredential` records
