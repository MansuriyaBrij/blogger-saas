<?php

use App\Jobs\BulkOperationJob;
use App\Jobs\DeletePostOnBloggerJob;
use App\Jobs\PublishPostJob;
use App\Jobs\RevertToDraftJob;
use App\Jobs\UpdatePostOnBloggerJob;
use App\Models\BloggerAccount;
use App\Models\BulkOperation;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

test('user can view their posts with filters', function () {
    $user    = User::factory()->create();
    $account = BloggerAccount::factory()->create(['user_id' => $user->id]);

    Post::factory()->create(['user_id' => $user->id, 'blogger_account_id' => $account->id, 'status' => 'LIVE', 'title' => 'Hello World']);
    Post::factory()->create(['user_id' => $user->id, 'blogger_account_id' => $account->id, 'status' => 'DRAFT', 'title' => 'Draft Post']);

    $this->actingAs($user)
        ->withSession(['selected_blog_id' => $account->id])
        ->get('/posts?status=LIVE')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Posts/Index')
            ->has('posts.data', 1)
        );
});

test('user can update a post', function () {
    Bus::fake();

    $user    = User::factory()->create();
    $account = BloggerAccount::factory()->create(['user_id' => $user->id]);
    $post    = Post::factory()->create(['user_id' => $user->id, 'blogger_account_id' => $account->id]);

    $this->actingAs($user)
        ->put("/posts/{$post->id}", [
            'title'   => 'Updated Title',
            'content' => '<p>Updated content</p>',
            'labels'  => ['php', 'laravel'],
            'status'  => 'LIVE',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Updated Title']);
    Bus::assertDispatched(UpdatePostOnBloggerJob::class);
});

test('user cannot update another users post', function () {
    $user      = User::factory()->create();
    $otherUser = User::factory()->create();
    $account   = BloggerAccount::factory()->create(['user_id' => $otherUser->id]);
    $post      = Post::factory()->create(['user_id' => $otherUser->id, 'blogger_account_id' => $account->id]);

    $this->actingAs($user)
        ->put("/posts/{$post->id}", [
            'title'   => 'Hacked',
            'content' => 'x',
            'status'  => 'DRAFT',
        ])
        ->assertForbidden();
});

test('toggle status dispatches correct job', function () {
    Bus::fake();

    $user    = User::factory()->create();
    $account = BloggerAccount::factory()->create(['user_id' => $user->id]);

    $livePost  = Post::factory()->create(['user_id' => $user->id, 'blogger_account_id' => $account->id, 'status' => 'LIVE']);
    $draftPost = Post::factory()->create(['user_id' => $user->id, 'blogger_account_id' => $account->id, 'status' => 'DRAFT']);

    $this->actingAs($user)->post("/posts/{$livePost->id}/toggle-status")->assertRedirect();
    Bus::assertDispatched(RevertToDraftJob::class);

    $this->actingAs($user)->post("/posts/{$draftPost->id}/toggle-status")->assertRedirect();
    Bus::assertDispatched(PublishPostJob::class);
});

test('bulk operation creates bulk_operation record', function () {
    Bus::fake();

    $user    = User::factory()->create();
    $account = BloggerAccount::factory()->create(['user_id' => $user->id]);
    $posts   = Post::factory(3)->create(['user_id' => $user->id, 'blogger_account_id' => $account->id]);

    $this->actingAs($user)
        ->postJson('/bulk', [
            'action'   => 'publish',
            'post_ids' => $posts->pluck('id')->toArray(),
        ])
        ->assertOk()
        ->assertJsonStructure(['message', 'id']);

    $this->assertDatabaseHas('bulk_operations', [
        'user_id' => $user->id,
        'type'    => 'publish',
        'total'   => 3,
        'status'  => 'pending',
    ]);

    Bus::assertDispatched(BulkOperationJob::class);
});

test('bulk operation job updates success and failed counts', function () {
    $user    = User::factory()->create();
    $account = BloggerAccount::factory()->create(['user_id' => $user->id]);
    $post    = Post::factory()->create([
        'user_id'            => $user->id,
        'blogger_account_id' => $account->id,
        'status'             => 'DRAFT',
        'blogger_post_id'    => 'post-123',
    ]);

    $bulk = BulkOperation::create([
        'user_id'            => $user->id,
        'blogger_account_id' => $account->id,
        'type'               => 'publish',
        'total'              => 1,
        'success_count'      => 0,
        'failed_count'       => 0,
        'error_log'          => [],
        'status'             => 'pending',
    ]);

    $mockService = Mockery::mock(\App\Services\BloggerService::class);
    $mockService->shouldReceive('publishPost')->once()->andReturn(null);
    app()->instance(\App\Services\BloggerService::class, $mockService);

    (new BulkOperationJob($bulk, [$post->id]))->handle();

    $bulk->refresh();
    expect($bulk->status)->toBe('done');
    expect($bulk->success_count)->toBe(1);
    expect($bulk->failed_count)->toBe(0);
});
