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
        Schema::create('authorizeds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->enum('group', ['merah', 'biru']);
            $table->string('quota');
            $table->string('is_active')->default('true');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authorizeds');
    }
};
