<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop unauthorizeds table
        Schema::dropIfExists('catera.unauthorizeds');

        // Rename registereds to quota_schedules using raw SQL for PostgreSQL
        DB::statement('ALTER TABLE catera.registereds RENAME TO quota_schedules');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore quota_schedules to registereds
        DB::statement('ALTER TABLE catera.quota_schedules RENAME TO registereds');

        // Restore unauthorizeds table
        Schema::create('catera.unauthorizeds', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->index()->unique();
            $table->timestamps();
        });
    }
};
