<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blogger_account_id')->constrained()->cascadeOnDelete();
            $table->string('blogger_post_id');
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('url')->nullable();
            $table->json('labels')->nullable();
            $table->enum('status', ['LIVE', 'DRAFT', 'SCHEDULED'])->default('DRAFT');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
