<?php

use App\Services\DashboardService;
use Livewire\Component;

new class extends Component {
    public string $startDate = '';
    public string $endDate = '';
    public array $stats = [];
    public array $trends = [];

    public function mount(): void
    {
        $this->startDate = now()->subMonth()->toDateString();
        $this->endDate = now()->addMonth()->toDateString();
    }

    public function with(): array
    {
        $service = app(DashboardService::class);

        $this->trends = $service->getTrends($this->startDate, $this->endDate);

        $this->stats = array_merge(
            $service->getStats(),
            $service->getTodayStats(),
            $service->getCategoryStats()
        );

        return [
            'stats' => $this->stats,
            'trends' => $this->trends,
        ];
    }
}; ?>

<x-slot name="title">Dashboard</x-slot>

<div class="flex h-full w-full flex-1 flex-col gap-6" x-data="{
    stats: @js($stats),
    trends: @js($trends),
    groupChartInstance: null,
    statusChartInstance: null,
    trendChartInstance: null,
    categoryChartInstance: null,
    init() {
        if (typeof window.ApexCharts === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
            script.async = true;
            script.onload = () => this.initCharts();
            document.head.appendChild(script);
        } else {
            this.initCharts();
        }

        this.$watch('$wire.trends', value => {
            if (this.trendChartInstance) {
                this.trendChartInstance.updateSeries([{
                    name: 'Quota Added',
                    data: value
                }]);
            }
        });

        this.$watch('$wire.stats', value => {
            if (this.groupChartInstance) {
                this.groupChartInstance.updateSeries([value.merah_count, value.biru_count]);
            }
            if (this.statusChartInstance) {
                this.statusChartInstance.updateSeries([{
                    name: 'Users',
                    data: [value.active_count, value.inactive_count]
                }]);
            }
            if (this.categoryChartInstance) {
                this.categoryChartInstance.updateSeries([{
                    name: 'Logs Count',
                    data: [
                        value.category_authorized,
                        value.category_wrong_group,
                        value.category_no_quota,
                        value.category_inactive,
                        value.category_not_registered
                    ]
                }]);
            }
        });
    },
    initCharts() {
        this.$refs.groupChart.innerHTML = '';
        this.$refs.statusChart.innerHTML = '';
        this.$refs.categoryChart.innerHTML = '';
        this.$refs.trendChart.innerHTML = '';

        this.groupChartInstance = new ApexCharts(this.$refs.groupChart, {
            chart: {
                type: 'donut',
                height: 320,
                fontFamily: 'inherit',
                animations: { enabled: true, easing: 'easeinout', speed: 800 }
            },
            series: [this.stats.merah_count, this.stats.biru_count],
            labels: ['Merah', 'Biru'],
            colors: ['#EF4444', '#3B82F6'],
            legend: { position: 'bottom', fontSize: '14px' },
            dataLabels: { enabled: true, dropShadow: { enabled: false } },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: () => this.stats.total_authorized
                            }
                        }
                    }
                }
            },
            stroke: { show: false }
        });
        this.groupChartInstance.render();

        this.statusChartInstance = new ApexCharts(this.$refs.statusChart, {
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            series: [{
                name: 'Users',
                data: [this.stats.active_count, this.stats.inactive_count]
            }],
            plotOptions: {
                bar: {
                    borderRadius: 12,
                    columnWidth: '45%',
                    distributed: true,
                    dataLabels: { position: 'top' }
                }
            },
            colors: ['#22C55E', '#71717A'],
            xaxis: {
                categories: ['Active', 'Inactive'],
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: { show: false },
            legend: { show: false },
            dataLabels: {
                enabled: true,
                offsetY: -20,
                style: { fontSize: '12px', colors: ['#5b5856'] }
            }
        });
        this.statusChartInstance.render();

        this.categoryChartInstance = new ApexCharts(this.$refs.categoryChart, {
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            series: [{
                name: 'Logs Count',
                data: [
                    this.stats.category_authorized,
                    this.stats.category_wrong_group,
                    this.stats.category_no_quota,
                    this.stats.category_inactive,
                    this.stats.category_not_registered
                ]
            }],
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 8,
                    barHeight: '60%',
                    distributed: true,
                    dataLabels: { position: 'right' }
                }
            },
            colors: ['#22C55E', '#F59E0B', '#EF4444', '#71717A', '#EC4899'],
            xaxis: {
                categories: ['Authorized', 'Wrong Group', 'No Quota', 'Inactive', 'Not Registered'],
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            legend: { show: false },
            dataLabels: {
                enabled: false
            }
        });
        this.categoryChartInstance.render();

        this.trendChartInstance = new ApexCharts(this.$refs.trendChart, {
            chart: {
                type: 'area',
                height: 350,
                fontFamily: 'inherit',
                toolbar: { show: false },
                zoom: { enabled: false }
            },
            series: [{
                name: 'Quota Added',
                data: this.trends
            }],
            xaxis: {
                type: 'datetime',
                labels: { datetimeUTC: false }
            },
            yaxis: { title: { text: 'Quota' } },
            colors: ['#3B82F6'],
            stroke: { curve: 'smooth', width: 3 },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.6,
                    opacityTo: 0.1,
                    stops: [0, 90, 100]
                }
            },
            markers: { size: 5, strokeWidth: 0, fillColor: '#3B82F6', strokeColors: '#3B82F6', hover: { size: 7 } },
            grid: { borderColor: '#e2e8f0' }
        });
        this.trendChartInstance.render();
    }
}">
    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="xl" level="1">Dashboard Overview</flux:heading>
            <flux:subheading size="lg">Real-time statistics and usage trends for lunch management.</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <flux:card class="group relative flex flex-col gap-2 overflow-hidden p-6 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 cursor-pointer">
            <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                <div class="p-2 rounded-lg bg-blue-500/10 text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                    <flux:icon name="users" variant="mini" />
                </div>
                <flux:text size="sm" font="medium">Total Authorized</flux:text>
            </div>
            <div class="flex items-baseline gap-2">
                <flux:heading size="xl" class="group-hover:text-blue-500 transition-colors">{{ number_format($stats['total_authorized']) }}</flux:heading>
                <flux:text size="xs" class="text-blue-500 font-semibold bg-blue-500/10 px-2 py-0.5 rounded-full">Verified</flux:text>
            </div>
            <div class="absolute -right-6 -bottom-6 opacity-[0.03] group-hover:opacity-[0.07] transition-opacity">
                 <flux:icon name="users" size="xl" class="size-32" />
            </div>
        </flux:card>

        <flux:card class="group relative flex flex-col gap-2 overflow-hidden p-6 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 cursor-pointer">
            <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                <div class="p-2 rounded-lg bg-violet-500/10 text-violet-500 group-hover:bg-violet-500 group-hover:text-white transition-colors">
                    <flux:icon name="document-text" variant="mini" />
                </div>
                <flux:text size="sm" font="medium">Access Logs (Today)</flux:text>
            </div>
            <div class="flex items-baseline gap-2 flex-wrap">
                <flux:heading size="xl" class="group-hover:text-violet-500 transition-colors">{{ number_format($stats['total_access_logs']) }}</flux:heading>
                <flux:text size="xs" class="text-green-600 font-semibold bg-green-100 dark:bg-green-900/30 dark:text-green-400 px-2 py-0.5 rounded-full">{{ number_format($stats['access_success_count']) }} Success</flux:text>
                <flux:text size="xs" class="text-red-600 font-semibold bg-red-100 dark:bg-red-900/30 dark:text-red-400 px-2 py-0.5 rounded-full">{{ number_format($stats['access_failed_count']) }} Failed</flux:text>
            </div>
             <div class="absolute -right-6 -bottom-6 opacity-[0.03] group-hover:opacity-[0.07] transition-opacity">
                 <flux:icon name="document-text" size="xl" class="size-32" />
            </div>
        </flux:card>

        <flux:card class="group relative flex flex-col gap-2 overflow-hidden p-6 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 cursor-pointer">
            <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                <div class="p-2 rounded-lg bg-amber-500/10 text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                    <flux:icon name="bolt" variant="mini" />
                </div>
                <flux:text size="sm" font="medium">Quota Distributed (This Month)</flux:text>
            </div>
            <div class="flex items-baseline gap-2">
                <flux:heading size="xl" class="group-hover:text-amber-500 transition-colors">{{ number_format($stats['total_quota']) }}</flux:heading>
                <flux:text size="xs" class="text-amber-500 font-semibold bg-amber-500/10 px-2 py-0.5 rounded-full">Units</flux:text>
            </div>
             <div class="absolute -right-6 -bottom-6 opacity-[0.03] group-hover:opacity-[0.07] transition-opacity">
                 <flux:icon name="bolt" size="xl" class="size-32" />
            </div>
        </flux:card>
    </div>

    {{-- Main Charts Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <flux:card class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Group Distribution</flux:heading>
                <flux:text size="xs" class="text-zinc-400">Color-coded Access</flux:text>
            </div>
            <div class="flex-1" x-ref="groupChart" wire:ignore></div>
        </flux:card>

        <flux:card class="flex flex-col gap-4">
             <div class="flex items-center justify-between">
                <flux:heading size="lg">Access Status</flux:heading>
                <flux:text size="xs" class="text-zinc-400">Active vs Inactive</flux:text>
            </div>
            <div class="flex-1" x-ref="statusChart" wire:ignore></div>
        </flux:card>

        <flux:card class="flex flex-col gap-4">
             <div class="flex items-center justify-between">
                <flux:heading size="lg">Access Log Categories</flux:heading>
                <flux:text size="xs" class="text-zinc-400">All-time status distribution</flux:text>
            </div>
            <div class="flex-1" x-ref="categoryChart" wire:ignore></div>
        </flux:card>
    </div>

    {{-- Trends Section --}}
    <flux:card class="flex flex-col gap-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-zinc-100 pb-4 dark:border-zinc-800 gap-4">
            <div>
                <flux:heading size="lg">Quota Addition History</flux:heading>
                <flux:subheading>Tracking the volume of quota additions over time.</flux:subheading>
            </div>
            <div class="flex items-center gap-2">
                <flux:input type="date" wire:model.live="startDate" size="sm" class="w-36" />
                <span class="text-zinc-400 text-sm">to</span>
                <flux:input type="date" wire:model.live="endDate" size="sm" class="w-36" />
            </div>
        </div>
        <div class="h-80 w-full" x-ref="trendChart" wire:ignore></div>
    </flux:card>
</div>
