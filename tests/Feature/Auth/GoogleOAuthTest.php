<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

test('google callback creates new user', function () {
    $googleUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
    $googleUser->token = 'fake-access-token';
    $googleUser->refreshToken = 'fake-refresh-token';
    $googleUser->expiresIn = 3600;
    $googleUser->shouldReceive('getId')->andReturn('google-uid-123');
    $googleUser->shouldReceive('getName')->andReturn('Test User');
    $googleUser->shouldReceive('getEmail')->andReturn('test@example.com');

    $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get('/auth/google/callback');

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('users', [
        'google_id' => 'google-uid-123',
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
});

test('google callback updates token for existing user', function () {
    $existingUser = User::factory()->create([
        'google_id' => 'google-uid-456',
        'name' => 'Old Name',
        'email' => 'existing@example.com',
        'google_access_token' => encrypt('old-token'),
        'google_refresh_token' => encrypt('old-refresh'),
    ]);

    $googleUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
    $googleUser->token = 'new-access-token';
    $googleUser->refreshToken = 'new-refresh-token';
    $googleUser->expiresIn = 3600;
    $googleUser->shouldReceive('getId')->andReturn('google-uid-456');
    $googleUser->shouldReceive('getName')->andReturn('Updated Name');
    $googleUser->shouldReceive('getEmail')->andReturn('existing@example.com');

    $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get('/auth/google/callback');

    $this->assertAuthenticated();
    expect(User::where('google_id', 'google-uid-456')->count())->toBe(1);
    expect($existingUser->fresh()->name)->toBe('Updated Name');
});

test('unauthenticated user is redirected to login', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect(route('login'));
});
