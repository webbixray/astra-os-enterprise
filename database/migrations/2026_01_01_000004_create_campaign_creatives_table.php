<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_creatives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->string('type', 30)->default('image');
            $table->json('content');
            $table->string('variant', 50)->nullable();
            $table->string('status', 20)->default('draft');
            $table->integer('version')->default(1);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index(['campaign_id', 'variant']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_creatives');
    }
};
