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
        // 1. Change scanned_at column type in access_logs
        // Using raw statement for pgsql to ensure proper type conversion
        DB::statement('ALTER TABLE access_logs ALTER COLUMN scanned_at TYPE timestamp(0) without time zone USING scanned_at::timestamp');

        // 2. Add indexes
        Schema::table('access_logs', function (Blueprint $table) {
            $table->index(['scanned_at', 'status']);
            $table->index(['scanned_at', 'group', 'status']);
            $table->index(['scanned_at', 'authorizeds_id']);
        });

        Schema::table('catera.authorizeds', function (Blueprint $table) {
            $table->index('is_active');
        });

        Schema::table('catera.quota_schedules', function (Blueprint $table) {
            $table->index(['target_date', 'add_quota']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catera.quota_schedules', function (Blueprint $table) {
            $table->dropIndex(['target_date', 'add_quota']);
        });

        Schema::table('catera.authorizeds', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('access_logs', function (Blueprint $table) {
            $table->dropIndex(['scanned_at', 'status']);
            $table->dropIndex(['scanned_at', 'group', 'status']);
            $table->dropIndex(['scanned_at', 'authorizeds_id']);
        });

        DB::statement('ALTER TABLE access_logs ALTER COLUMN scanned_at TYPE varchar(255) USING scanned_at::varchar');
    }
};
