<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AuthorizedSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('seeders/data/catera_authorized.csv');
        $file = fopen($csvPath, 'r');

        if ($file === false) {
            $this->command->error('CSV file not found at: '.$csvPath);

            return;
        }

        // Skip header row
        fgetcsv($file);

        $data = [];
        $now = now();

        $groups = \App\Models\MdGroup::pluck('id', 'nama_group');

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 6) {
                continue;
            }

            $groupName = $row[3];
            $groupId = $groups[$groupName] ?? null;

            // Also update portal user's status to active/inactive since we dropped is_active column from authorizeds
            $isActive = strtolower($row[5]) === 'true';
            \Illuminate\Support\Facades\DB::table('portal_application.md_users')
                ->where('id', (int) $row[2])
                ->update(['status' => $isActive ? 'active' : 'inactive']);

            // Directly run updateOrInsert to avoid duplicate UUID unique constraints or bulk issues in Postgres
            \Illuminate\Support\Facades\DB::table('catera.authorizeds')->updateOrInsert(
                ['uuid' => $row[1]],
                [
                    'user_id' => (int) $row[2],
                    'group_id' => $groupId,
                    'quota' => (int) $row[4],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        fclose($file);
    }
}
