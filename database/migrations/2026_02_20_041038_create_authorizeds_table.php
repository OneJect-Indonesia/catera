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
        Schema::create('catera.authorizeds', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->index()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('group')->index();
            $table->fullText(['uuid', 'group', 'first_name', 'last_name']);
            $table->integer('quota');
            $table->fullText(['uuid', 'group']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catera.authorizeds');
    }
};
