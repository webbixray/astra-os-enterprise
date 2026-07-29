<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('agent_id')->constrained('agents')->onDelete('cascade');
            $table->string('type', 20)->default('episodic');
            $table->string('key', 100);
            $table->json('content');
            $table->tinyInteger('importance')->default(1);
            $table->integer('access_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->unique(['agent_id', 'key']);
            $table->index('type');
            $table->index('importance');
            $table->index(['agent_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_memories');
    }
};
