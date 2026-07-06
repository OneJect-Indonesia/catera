<?php

namespace App\Console\Commands;

use App\Mail\WeeklyAccessLogExportMail;
use App\Models\AccessLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ExportWeeklyAccessLogs extends Command
{
    protected $signature = 'app:export-weekly-access-logs';

    protected $description = 'Export authorized access logs from the past week and email the link';

    public function handle(): int
    {
        $this->info('Starting weekly access log export...');

        $startDate = now()->subWeek()->startOfDay();
        $endDate = now()->subDay()->endOfDay();

        $filename = 'authorized_access_logs_'.now()->subWeek()->format('Y-m-d').'_to_'.now()->subDay()->format('Y-m-d').'.csv';
        $path = 'exports/'.$filename;

        Storage::disk('local')->makeDirectory('exports');

        $logs = AccessLog::query()
            ->select(
                'portal_application.md_users.first_name',
                'portal_application.md_users.last_name',
                'portal_application.md_users.nik',
                'catera.access_logs.group as type',
                'catera.access_logs.scanned_at as tanggal_pengambilan'
            )
            ->join('catera.authorizeds', 'catera.access_logs.authorizeds_id', '=', 'catera.authorizeds.id')
            ->join('portal_application.md_users', 'catera.authorizeds.user_id', '=', 'portal_application.md_users.id')
            ->where('catera.access_logs.status', 'authorized')
            ->whereBetween('catera.access_logs.scanned_at', [$startDate, $endDate])
            ->orderBy('catera.access_logs.scanned_at')
            ->get();

        if ($logs->isEmpty()) {
            $this->info('No authorized logs found for the past week. Aborting.');

            return self::SUCCESS;
        }

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['Full Name', 'NIK', 'Type', 'Tanggal Pengambilan']);

        foreach ($logs as $log) {
            $fullName = trim(($log->first_name ?? '').' '.($log->last_name ?? ''));
            fputcsv($handle, [
                $fullName,
                $log->nik,
                $log->type,
                \Carbon\Carbon::parse($log->tanggal_pengambilan)->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('local')->put($path, $csvContent);

        $downloadUrl = URL::temporarySignedRoute(
            'exports.download',
            now()->addDays(7),
            ['filename' => $filename]
        );

        $users = User::whereHas('permissions', function ($query) {
            $query->where('name', 'catera:access_logs:view_any');
        })->get();

        foreach ($users as $user) {
            Mail::to($user->email)->send(new WeeklyAccessLogExportMail($downloadUrl));
        }

        $this->info("Exported {$logs->count()} records to {$path}.");
        $this->info('Email sent to '.$users->count().' users.');

        return self::SUCCESS;
    }
}
