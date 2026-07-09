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
        // Insert standard groups first so we can map old strings
        DB::table('catera.md_groups')->insertOrIgnore([
            ['nama_group' => 'merah', 'short_description' => 'Group Merah', 'created_at' => now(), 'updated_at' => now()],
            ['nama_group' => 'biru', 'short_description' => 'Group Biru', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('catera.authorizeds', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('group')->constrained('catera.md_groups')->nullOnDelete();
        });

        // Migrate string group to group_id
        $groups = DB::table('catera.md_groups')->pluck('id', 'nama_group');
        foreach ($groups as $name => $id) {
            DB::table('catera.authorizeds')->where('group', $name)->update(['group_id' => $id]);
        }

        // Dropping indexes in Blueprint must be run in individual statement blocks if we want to catch exceptions,
        // because Laravel compiles and executes all operations in a single ALTER TABLE query inside one block.
        // Instead of calling $table->dropIndex() inside the callback which throws immediately if it fails,
        // we can safely execute raw SQL queries to drop the index if it exists in Postgres.
        try {
            DB::statement('DROP INDEX IF EXISTS catera.catera_authorizeds_uuid_group_index');
            DB::statement('DROP INDEX IF EXISTS catera_authorizeds_uuid_group_index');
            DB::statement('DROP INDEX IF EXISTS authorizeds_uuid_group_fulltext');
        } catch (\Exception $e) {
        }

        Schema::table('catera.authorizeds', function (Blueprint $table) {
            $table->dropColumn('group');
            $table->dropColumn('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catera.authorizeds', function (Blueprint $table) {
            $table->string('group')->nullable()->index();
            $table->boolean('is_active')->default(true);
        });

        // Populate group string back from group_id relationship
        $authorizeds = DB::table('catera.authorizeds')
            ->join('catera.md_groups', 'catera.authorizeds.group_id', '=', 'catera.md_groups.id')
            ->select('catera.authorizeds.id', 'catera.md_groups.nama_group')
            ->get();

        foreach ($authorizeds as $auth) {
            DB::table('catera.authorizeds')->where('id', $auth->id)->update(['group' => $auth->nama_group]);
        }

        Schema::table('catera.authorizeds', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
            $table->fullText(['uuid', 'group']);
        });
    }
};
