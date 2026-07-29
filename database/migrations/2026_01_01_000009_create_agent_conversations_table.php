<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('session_id', 100);
            $table->foreignUuid('agent_id')->constrained('agents')->onDelete('cascade');
            $table->json('messages');
            $table->json('context')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->index(['agent_id', 'session_id']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_conversations');
    }
};
