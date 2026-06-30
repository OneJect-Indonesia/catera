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
            $table->foreignId('user_id')->constrained('portal_application.md_users')->unique();
            $table->string('group')->index();
            $table->integer('quota');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->fullText(['uuid', 'group']);
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
