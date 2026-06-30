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
        if (app()->environment('testing')) {
            // Ensure schemas exist in the testing database
            DB::statement('CREATE SCHEMA IF NOT EXISTS catera');
            DB::statement('CREATE SCHEMA IF NOT EXISTS portal_application');

            // Create the mock md_users table to satisfy foreign key constraints
            Schema::create('portal_application.md_users', function (Blueprint $table) {
                $table->id();
                $table->string('nik')->nullable();
                $table->string('email')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->bigInteger('department_id')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });

            // Create Spatie md_permissions
            Schema::create('portal_application.md_permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();

                $table->unique(['name', 'guard_name']);
            });

            // Seed required permissions for testing
            DB::table('portal_application.md_permissions')->insert([
                ['name' => 'catera:authorized:viewAny', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'catera:authorized:view', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'catera:authorized:create', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'catera:authorized:update', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'catera:authorized:delete', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'catera:authorized:view_any', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'catera:quota_scheduling:view_any', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'catera:access_logs:view_any', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'catera:dashboard:view', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ]);

            // Create Spatie md_roles
            Schema::create('portal_application.md_roles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();

                $table->unique(['name', 'guard_name']);
            });

            // Create Spatie md_model_has_permissions
            Schema::create('portal_application.md_model_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'md_model_has_permissions_model_id_model_type_index');

                $table->foreign('permission_id')
                    ->references('id')
                    ->on('portal_application.md_permissions')
                    ->onDelete('cascade');

                $table->primary(['permission_id', 'model_id', 'model_type'],
                    'md_model_has_permissions_permission_model_type_primary');
            });

            // Create Spatie md_model_has_roles
            Schema::create('portal_application.md_model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'md_model_has_roles_model_id_model_type_index');

                $table->foreign('role_id')
                    ->references('id')
                    ->on('portal_application.md_roles')
                    ->onDelete('cascade');

                $table->primary(['role_id', 'model_id', 'model_type'],
                    'md_model_has_roles_role_model_type_primary');
            });

            // Create Spatie md_role_has_permissions
            Schema::create('portal_application.md_role_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');

                $table->foreign('permission_id')
                    ->references('id')
                    ->on('portal_application.md_permissions')
                    ->onDelete('cascade');

                $table->foreign('role_id')
                    ->references('id')
                    ->on('portal_application.md_roles')
                    ->onDelete('cascade');

                $table->primary(['permission_id', 'role_id'], 'md_role_has_permissions_permission_id_role_id_primary');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (app()->environment('testing')) {
            Schema::dropIfExists('portal_application.md_role_has_permissions');
            Schema::dropIfExists('portal_application.md_model_has_roles');
            Schema::dropIfExists('portal_application.md_model_has_permissions');
            Schema::dropIfExists('portal_application.md_roles');
            Schema::dropIfExists('portal_application.md_permissions');
            Schema::dropIfExists('portal_application.md_users');
        }
    }
};
