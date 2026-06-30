<?php

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
        Schema::table('catera.registereds', function (Blueprint $table) {
            $table->date('target_date')->after('add_quota')->nullable();
            $table->string('status')->after('target_date')->default('pending');
            $table->index('authorized_uuid');
            $table->index(['status', 'target_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catera.registereds', function (Blueprint $table) {
            $table->dropColumn(['target_date', 'status']);
            $table->dropIndex(['authorized_uuid']);
            $table->dropIndex(['status', 'target_date']);
        });
    }
};
