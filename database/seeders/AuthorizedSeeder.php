<?php

namespace Database\Seeders;

use App\Models\Authorized;
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

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 6) {
                continue;
            }

            $data[] = [
                'uuid' => $row[1],
                'user_id' => (int) $row[2],
                'group' => $row[3],
                'quota' => (int) $row[4],
                'is_active' => strtolower($row[5]) === 'true',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($data) >= 500) {
                Authorized::insert($data);
                $data = [];
            }
        }

        if (count($data) > 0) {
            Authorized::insert($data);
        }

        fclose($file);
    }
}
