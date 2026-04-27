<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefreshGoogleTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        if (! $this->user->google_token_expires_at || $this->user->google_token_expires_at->diffInSeconds(now()) > 300) {
            return;
        }

        $refreshToken = decrypt($this->user->google_refresh_token);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            Log::error('Google token refresh failed', ['user_id' => $this->user->id, 'response' => $response->body()]);
            $this->fail('Google token refresh failed.');
            return;
        }

        $data = $response->json();

        $this->user->update([
            'google_access_token' => encrypt($data['access_token']),
            'google_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);
    }
}
