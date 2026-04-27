<?php

use App\Events\SyncCompleted;
use App\Jobs\SyncBlogPostsJob;
use App\Models\BloggerAccount;
use App\Models\Label;
use App\Models\Post;
use App\Models\User;
use App\Services\BloggerService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

test('authenticated user can connect blogger accounts', function () {
    Bus::fake();
    Event::fake();

    $user = User::factory()->create([
        'google_access_token' => encrypt('fake-token'),
        'google_refresh_token' => encrypt('fake-refresh'),
        'google_token_expires_at' => now()->addHour(),
    ]);

    $mockService = Mockery::mock(BloggerService::class);
    $mockService->shouldReceive('getBlogs')->once()->andReturn([
        ['id' => 'blog-001', 'name' => 'My Tech Blog', 'url' => 'https://mytechblog.blogspot.com'],
        ['id' => 'blog-002', 'name' => 'My Travel Blog', 'url' => 'https://mytravel.blogspot.com'],
    ]);

    $this->app->instance(BloggerService::class, $mockService);

    $response = $this->actingAs($user)->post('/blogs/connect');

    $response->assertRedirect();

    $this->assertDatabaseHas('blogger_accounts', [
        'user_id' => $user->id,
        'blog_id' => 'blog-001',
        'blog_name' => 'My Tech Blog',
    ]);

    $this->assertDatabaseHas('blogger_accounts', [
        'user_id' => $user->id,
        'blog_id' => 'blog-002',
    ]);

    Bus::assertDispatched(SyncBlogPostsJob::class, 2);
});

test('sync job saves posts to database correctly', function () {
    Event::fake();

    $user = User::factory()->create([
        'google_access_token' => encrypt('fake-token'),
        'google_refresh_token' => encrypt('fake-refresh'),
        'google_token_expires_at' => now()->addHour(),
    ]);

    $account = BloggerAccount::factory()->create(['user_id' => $user->id, 'blog_id' => 'blog-001']);

    $mockService = Mockery::mock(BloggerService::class);
    $mockService->shouldReceive('getPosts')->with('blog-001')->once()->andReturn([
        [
            'id' => 'post-aaa',
            'title' => 'Hello World',
            'content' => '<p>Content here</p>',
            'url' => 'https://example.com/hello-world',
            'labels' => ['php', 'laravel'],
            'status' => 'LIVE',
            'published' => '2024-01-15T10:00:00Z',
        ],
    ]);

    $this->app->instance(BloggerService::class, $mockService);

    (new SyncBlogPostsJob($user, $account))->handle();

    $this->assertDatabaseHas('posts', [
        'blogger_account_id' => $account->id,
        'blogger_post_id' => 'post-aaa',
        'title' => 'Hello World',
        'status' => 'LIVE',
    ]);

    expect(Post::where('blogger_account_id', $account->id)->count())->toBe(1);
});

test('sync job extracts and saves labels', function () {
    Event::fake();

    $user = User::factory()->create([
        'google_access_token' => encrypt('fake-token'),
        'google_refresh_token' => encrypt('fake-refresh'),
        'google_token_expires_at' => now()->addHour(),
    ]);

    $account = BloggerAccount::factory()->create(['user_id' => $user->id, 'blog_id' => 'blog-002']);

    $mockService = Mockery::mock(BloggerService::class);
    $mockService->shouldReceive('getPosts')->with('blog-002')->once()->andReturn([
        ['id' => 'p1', 'title' => 'Post 1', 'content' => '', 'url' => '', 'labels' => ['php', 'laravel'], 'status' => 'LIVE', 'published' => null],
        ['id' => 'p2', 'title' => 'Post 2', 'content' => '', 'url' => '', 'labels' => ['php', 'mysql'], 'status' => 'DRAFT', 'published' => null],
    ]);

    $this->app->instance(BloggerService::class, $mockService);

    (new SyncBlogPostsJob($user, $account))->handle();

    expect(Label::where('blogger_account_id', $account->id)->count())->toBe(3);
    expect(Label::where('blogger_account_id', $account->id)->where('name', 'php')->value('post_count'))->toBe(2);
    expect(Label::where('blogger_account_id', $account->id)->where('name', 'laravel')->value('post_count'))->toBe(1);
});

test('switch blog saves to session', function () {
    $user = User::factory()->create();
    $account = BloggerAccount::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->postJson('/blogs/switch', ['blog_id' => $account->id]);

    $response->assertJson(['success' => true]);
    expect(session('selected_blog_id'))->toBe($account->id);
});

test('user cannot switch to another users blog', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherAccount = BloggerAccount::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->postJson('/blogs/switch', ['blog_id' => $otherAccount->id]);

    $response->assertForbidden();
});
