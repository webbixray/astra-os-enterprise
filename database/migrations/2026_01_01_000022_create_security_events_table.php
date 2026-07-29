<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates a dedicated security_events table for high-granularity
     * security audit records.  Unlike the general audit_logs table, this
     * table stores structured event metadata with severity classification
     * and is optimised for security-specific query patterns (event_type,
     * severity, time range).
     */
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('event_type', 128);
            $table->string('severity', 16); // info, warning, critical
            $table->json('details')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Performance indexes for common security queries.
            $table->index('event_type');
            $table->index('severity');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
