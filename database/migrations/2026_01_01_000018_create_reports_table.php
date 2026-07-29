<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('name');
            $table->string('type', 30);
            $table->json('config');
            $table->string('schedule')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->string('format', 10)->default('pdf');
            $table->json('recipients')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('schedule');
            $table->index(['organization_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
