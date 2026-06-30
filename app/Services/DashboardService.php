<?php

namespace App\Services;

use App\Models\AccessLog;
use App\Models\Authorized;
use App\Models\QuotaSchedule;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function getStats(): array
    {
        return Cache::remember('dashboard.stats', 300, fn () => $this->fetchStats());
    }

    public function getTrends(?string $startDate, ?string $endDate): array
    {
        return Cache::remember(
            'dashboard.trends.'.md5($startDate.$endDate),
            300,
            fn () => $this->fetchTrends($startDate, $endDate)
        );
    }

    public function getTodayStats(): array
    {
        $dateKey = now()->toDateString();

        return Cache::remember("dashboard.today.{$dateKey}", 60, fn () => $this->fetchTodayStats());
    }

    public function getCategoryStats(): array
    {
        return Cache::remember('dashboard.categories', 300, fn () => $this->fetchCategoryStats());
    }

    protected function fetchStats(): array
    {
        $authorizedStats = Authorized::query()
            ->selectRaw(
                'COUNT(*) as total,
                SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN is_active = false THEN 1 ELSE 0 END) as inactive_count,
                SUM(CASE WHEN "group" = ? THEN 1 ELSE 0 END) as merah_count,
                SUM(CASE WHEN "group" = ? THEN 1 ELSE 0 END) as biru_count',
                ['merah', 'biru']
            )
            ->first();

        $totalQuota = QuotaSchedule::query()
            ->currentMonth()
            ->sum('add_quota');

        return [
            'total_authorized' => (int) $authorizedStats->total,
            'total_quota' => (int) $totalQuota,
            'active_count' => (int) $authorizedStats->active_count,
            'inactive_count' => (int) $authorizedStats->inactive_count,
            'merah_count' => (int) $authorizedStats->merah_count,
            'biru_count' => (int) $authorizedStats->biru_count,
        ];
    }

    protected function fetchTrends(?string $startDate, ?string $endDate): array
    {
        return QuotaSchedule::query()
            ->selectRaw('target_date as date, SUM(add_quota) as total')
            ->whereNotNull('target_date')
            ->inDateRange($startDate, $endDate)
            ->groupBy('target_date')
            ->orderBy('target_date', 'asc')
            ->get()
            ->map(fn ($item) => [
                'x' => strtotime((string) $item->date) * 1000,
                'y' => (int) $item->total,
            ])
            ->toArray();
    }

    protected function fetchTodayStats(): array
    {
        $stats = AccessLog::query()
            ->today()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $successCount = (int) ($stats->get('authorized') ?? 0);
        $totalCount = (int) $stats->sum();

        return [
            'total_access_logs' => $totalCount,
            'access_success_count' => $successCount,
            'access_failed_count' => $totalCount - $successCount,
        ];
    }

    protected function fetchCategoryStats(): array
    {
        $stats = AccessLog::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'category_authorized' => (int) ($stats->get('authorized') ?? 0),
            'category_wrong_group' => (int) ($stats->get('wrong group') ?? 0),
            'category_no_quota' => (int) ($stats->get('no quota') ?? 0),
            'category_inactive' => (int) ($stats->get('inactive') ?? 0),
            'category_not_registered' => (int) ($stats->get('not registered') ?? 0),
        ];
    }
}
