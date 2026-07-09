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
        Schema::create('catera.md_groups', function (Blueprint $table) {
            $table->id();
            $table->string('nama_group')->unique();
            $table->string('short_description')->nullable();
            $table->string('color')->default('zinc');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catera.md_groups');
    }
};
