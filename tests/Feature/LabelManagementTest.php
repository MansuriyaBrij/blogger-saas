<?php

use App\Jobs\DeleteLabelJob;
use App\Jobs\MergeLabelJob;
use App\Jobs\RenameLabelJob;
use App\Models\BloggerAccount;
use App\Models\Label;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

test('user can view labels for selected blog', function () {
    $user    = User::factory()->create();
    $account = BloggerAccount::factory()->create(['user_id' => $user->id]);

    Label::factory()->create(['user_id' => $user->id, 'blogger_account_id' => $account->id, 'name' => 'php']);
    Label::factory()->create(['user_id' => $user->id, 'blogger_account_id' => $account->id, 'name' => 'laravel']);

    $this->actingAs($user)
        ->withSession(['selected_blog_id' => $account->id])
        ->get('/labels')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Labels/Index')
            ->has('labels', 2)
        );
});

test('rename label dispatches RenameLabelJob', function () {
    Bus::fake();

    $user    = User::factory()->create();
    $account = BloggerAccount::factory()->create(['user_id' => $user->id]);
    $label   = Label::factory()->create(['user_id' => $user->id, 'blogger_account_id' => $account->id, 'name' => 'old-name']);

    $this->actingAs($user)
        ->put("/labels/{$label->id}/rename", ['name' => 'new-name'])
        ->assertRedirect();

    Bus::assertDispatched(RenameLabelJob::class, fn ($job) => $job->label->id === $label->id && $job->newName === 'new-name');
});

test('merge label dispatches MergeLabelJob', function () {
    Bus::fake();

    $user    = User::factory()->create();
    $account = BloggerAccount::factory()->create(['user_id' => $user->id]);
    $source  = Label::factory()->create(['user_id' => $user->id, 'blogger_account_id' => $account->id]);
    $target  = Label::factory()->create(['user_id' => $user->id, 'blogger_account_id' => $account->id]);

    $this->actingAs($user)
        ->post('/labels/merge', ['source_id' => $source->id, 'target_id' => $target->id])
        ->assertRedirect();

    Bus::assertDispatched(MergeLabelJob::class);
});

test('user cannot manage another users label', function () {
    Bus::fake();

    $user      = User::factory()->create();
    $otherUser = User::factory()->create();
    $account   = BloggerAccount::factory()->create(['user_id' => $otherUser->id]);
    $label     = Label::factory()->create(['user_id' => $otherUser->id, 'blogger_account_id' => $account->id]);

    $this->actingAs($user)
        ->put("/labels/{$label->id}/rename", ['name' => 'hacked'])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete("/labels/{$label->id}")
        ->assertForbidden();

    Bus::assertNothingDispatched();
});
