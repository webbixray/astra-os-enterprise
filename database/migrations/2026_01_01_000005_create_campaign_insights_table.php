<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->date('date');
            $table->string('metric', 50);
            $table->decimal('value', 18, 4);
            $table->string('source', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'date']);
            $table->index(['campaign_id', 'metric']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_insights');
    }
};
