<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('post_id')->constrained('social_posts')->onDelete('cascade');
            $table->string('platform', 30);
            $table->string('author_name');
            $table->string('author_id');
            $table->text('content');
            $table->string('sentiment', 20)->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->boolean('is_replied')->default(false);
            $table->text('ai_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->index('sentiment');
            $table->index('is_flagged');
            $table->index(['post_id', 'is_flagged']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_comments');
    }
};
