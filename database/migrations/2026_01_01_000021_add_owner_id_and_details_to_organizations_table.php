<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'owner_id')) {
                $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('organizations', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('organizations', 'logo')) {
                $table->string('logo', 2048)->nullable()->after('description');
            }
            if (!Schema::hasColumn('organizations', 'website')) {
                $table->string('website', 2048)->nullable()->after('logo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['owner_id', 'description', 'logo', 'website']);
        });
    }
};
