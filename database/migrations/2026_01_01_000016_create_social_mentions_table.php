<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_mentions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('platform', 30);
            $table->string('mention_url');
            $table->string('author_name');
            $table->text('content');
            $table->string('sentiment', 20)->nullable();
            $table->bigInteger('reach')->nullable();
            $table->text('ai_suggested_response')->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamps();

            $table->index('platform');
            $table->index('sentiment');
            $table->index('status');
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_mentions');
    }
};
