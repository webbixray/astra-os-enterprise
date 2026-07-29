<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('name');
            $table->string('role', 30)->default('specialist');
            $table->json('model_config');
            $table->string('autonomy_level', 20)->default('supervised');
            $table->foreignUuid('parent_agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->json('capabilities')->nullable();
            $table->text('instructions')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('role');
            $table->index('autonomy_level');
            $table->index('is_active');
            $table->index(['organization_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
