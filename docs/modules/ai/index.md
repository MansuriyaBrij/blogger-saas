# AI Module

## Responsibility

Abstracts all AI provider calls behind a driver pattern so the rest of the app calls `AiProviderManager::generate($prompt)` without knowing which provider is active. Each user supplies and encrypts their own API keys.

## Classes

| Class | Role |
|---|---|
| `App\Services\Ai\AiProviderManager` | Resolves the correct driver for the user's default (or specified) provider |
| `App\Services\Ai\Drivers\OpenAiDriver` | Implements `AiDriver` for the OpenAI API |
| `App\Services\Ai\Drivers\AnthropicDriver` | Implements `AiDriver` for the Anthropic Claude API |
| `App\Services\Ai\Drivers\GeminiDriver` | Implements `AiDriver` for Google Gemini API |
| `App\Models\AiCredential` | Stores encrypted API key + default model per provider per user |
| `App\Http\Controllers\Settings\AiProviderController` | CRUD for AI credentials; triggers key validation on save |
| `App\Exceptions\AiKeyInvalidException` | Thrown when `validateKey()` fails |

## Contract

```php
namespace App\Services\Ai\Contracts;

interface AiDriver
{
    /**
     * Send a prompt and return the completion text.
     *
     * @throws \App\Exceptions\AiGenerationException
     */
    public function generate(string $prompt, array $options = []): string;

    /**
     * Make a lightweight test call to verify the stored API key.
     *
     * @throws \App\Exceptions\AiKeyInvalidException
     */
    public function validateKey(): void;

    /**
     * Return the provider slug: 'openai' | 'anthropic' | 'gemini'.
     */
    public function provider(): string;
}
```

## Models

### AiCredential

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | bigint | FK → users, cascade delete |
| `provider` | enum | `openai`, `anthropic`, `gemini` |
| `api_key` | text | Encrypted AES-256-CBC |
| `default_model` | string | e.g. `gpt-4o`, `claude-sonnet-4-6`, `gemini-1.5-pro` |
| `is_default` | boolean | Only one true per user; others false on update |
| `created_at` / `updated_at` | timestamps | — |

## Key Methods

| Method | Class | Description |
|---|---|---|
| `driver(?string $provider): AiDriver` | `AiProviderManager` | Returns driver for named provider, or user's default if null |
| `generate(string $prompt, array $options): string` | Each Driver | Calls provider HTTP API; returns completion string |
| `validateKey(): void` | Each Driver | Sends a minimal test prompt; throws `AiKeyInvalidException` on non-2xx |
| `store(Request): JsonResponse` | `AiProviderController` | Validates, calls `validateKey()`, encrypts and saves; returns success or error |

## Notes

- `api_key` is encrypted with `encrypt()` and listed in `$hidden` — never serialised to JSON.
- The Settings page displays only `****` + last 4 characters of the stored key.
- `is_default = true` is enforced with a DB-level `before save` check: when one credential is set default, all others for that user are set to false in the same transaction.
- Model IDs to use (as of June 2026): OpenAI `gpt-4o`, Anthropic `claude-sonnet-4-6`, Gemini `gemini-1.5-pro`. These are stored in `default_model`, not hard-coded — users can override.
- See the [claude-api skill](../../../features/05-ai-providers/index.md) for current model pricing before choosing defaults.
