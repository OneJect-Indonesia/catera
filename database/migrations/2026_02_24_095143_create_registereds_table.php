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
        Schema::create('registereds', function (Blueprint $table) {
            $table->id();
            $table->string('authorized_uuid');
            $table->integer('add_quota')->default(0);
            $table->timestamps();

            $table->foreign('authorized_uuid')->references('uuid')->on('authorizeds')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registereds');
    }
};
