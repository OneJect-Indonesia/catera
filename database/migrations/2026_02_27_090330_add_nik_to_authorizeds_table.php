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
        Schema::table('authorizeds', function (Blueprint $table) {
            $table->string('nik')->after('last_name')->nullable();
            $table->dropFullText(['uuid', 'group', 'first_name', 'last_name']);
            $table->fullText(['uuid', 'nik', 'group', 'first_name', 'last_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('authorizeds', function (Blueprint $table) {
            $table->dropFullText(['uuid', 'nik', 'group', 'first_name', 'last_name']);
            $table->fullText(['uuid', 'group', 'first_name', 'last_name']);
            $table->dropColumn('nik');
        });
    }
};
