<?php

use App\Models\AccessLog;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterGroup = '';
    public string $filterStatus = '';
    public string $startDate = '';
    public string $endDate = '';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'filterGroup', 'filterStatus', 'startDate', 'endDate'])) {
            $this->resetPage();
        }
    }

    public function with(): array
    {
        $this->authorize('viewAny', AccessLog::class);

        return [
            'accessLogs' => AccessLog::query()
                ->select('catera.access_logs.*')
                ->with('authorized.user')
                ->when($this->search, function ($query) {
                    $query->leftJoin('catera.authorizeds', 'catera.access_logs.authorizeds_id', '=', 'catera.authorizeds.id')
                        ->leftJoin('portal_application.md_users', 'catera.authorizeds.user_id', '=', 'portal_application.md_users.id')
                        ->where(function ($q) {
                            $q->where('catera.access_logs.uuid', 'ilike', $this->search . '%')
                                ->orWhere('catera.access_logs.group', 'ilike', $this->search . '%')
                                ->orWhere('catera.access_logs.status', 'ilike', $this->search . '%')
                                ->orWhere('portal_application.md_users.first_name', 'ilike', $this->search . '%')
                                ->orWhere('portal_application.md_users.last_name', 'ilike', $this->search . '%')
                                ->orWhere('portal_application.md_users.nik', 'ilike', $this->search . '%');
                        });
                })
                ->when($this->filterGroup, fn ($q) => $q->where('catera.access_logs.group', $this->filterGroup))
                ->when($this->filterStatus, fn ($q) => $q->where('catera.access_logs.status', $this->filterStatus))
                ->when($this->startDate, fn ($q) => $q->where('catera.access_logs.scanned_at', '>=', $this->startDate))
                ->when($this->endDate, fn ($q) => $q->where('catera.access_logs.scanned_at', '<=', $this->endDate))
                ->orderByDesc('catera.access_logs.scanned_at')
                ->paginate(15),
        ];
    }
}; ?>

<x-slot name="title">Access Logs</x-slot>

<div class="flex h-full w-full flex-1 flex-col gap-6">

    {{-- Page Header --}}
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="xl" level="1">Access Logs</flux:heading>
            <flux:subheading size="lg">View scan and access history for all authorized users.</flux:subheading>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" x-data="{ showFilters: false }">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:input
                wire:model.live="search"
                icon="magnifying-glass"
                placeholder="Search by UUID, group, status, or user..."
                class="w-full sm:max-w-xs"
            />
            @php
                $activeFilterCount = collect([$filterGroup, $filterStatus, $startDate, $endDate])->filter()->count();
            @endphp
            <flux:button @click="showFilters = !showFilters" icon="funnel" :variant="$activeFilterCount > 0 ? 'primary' : 'filled'">
                Filter @if($activeFilterCount > 0) ({{ $activeFilterCount }}) @endif
            </flux:button>
        </div>

        <div x-show="showFilters" x-transition class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4 border-t border-zinc-100 pt-4 dark:border-zinc-800">
            {{-- Group Filter --}}
            <flux:select wire:model.live="filterGroup" label="Group" placeholder="All Groups">
                <flux:select.option value="">All Groups</flux:select.option>
                <flux:select.option value="merah">Merah</flux:select.option>
                <flux:select.option value="biru">Biru</flux:select.option>
            </flux:select>

            {{-- Status Filter --}}
            <flux:select wire:model.live="filterStatus" label="Status" placeholder="All Statuses">
                <flux:select.option value="">All Statuses</flux:select.option>
                <flux:select.option value="authorized">Authorized</flux:select.option>
                <flux:select.option value="wrong group">Wrong Group</flux:select.option>
                <flux:select.option value="no quota">No Quota</flux:select.option>
                <flux:select.option value="inactive">Inactive</flux:select.option>
                <flux:select.option value="not registered">Not Registered</flux:select.option>
            </flux:select>

            {{-- Start Date --}}
            <flux:input type="date" wire:model.live="startDate" label="Start Date" />

            {{-- End Date --}}
            <flux:input type="date" wire:model.live="endDate" label="End Date" />

            {{-- Reset Button --}}
            <div class="col-span-1 sm:col-span-4 flex justify-end gap-2 mt-2">
                <flux:button size="sm" wire:click="$set('filterGroup', ''); $set('filterStatus', ''); $set('startDate', ''); $set('endDate', '');" variant="ghost">Reset Filters</flux:button>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">UUID</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Full Name</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Group</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Scanned At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($accessLogs as $log)
                        @php
                            $fullName = trim(($log->authorized?->user?->first_name ?? '') . ' ' . ($log->authorized?->user?->last_name ?? ''));
                            $statusColor = match(strtolower($log->status)) {
                                'authorized' => 'green',
                                'wrong group' => 'yellow',
                                'no quota' => 'red',
                                'inactive' => 'zinc',
                                'not registered' => 'pink',
                                default => 'zinc',
                            };
                        @endphp
                        <tr class="transition-colors duration-150 hover:bg-hover/20 dark:hover:bg-hover/30" wire:key="access-log-{{ $log->id }}">
                            <td class="px-4 py-3.5 text-center">
                                <span class="font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $log->uuid }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $fullName ?: '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <flux:badge size="sm" :color="$log->group === 'merah' ? 'red' : 'blue'" inset="top bottom" class="w-20 justify-center">
                                    {{ ucfirst($log->group) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <flux:badge size="sm" :color="$statusColor" inset="top bottom" class="w-24 justify-center">
                                    {{ ucfirst($log->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $log->scanned_at }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-400 dark:text-zinc-500">
                                No access logs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($accessLogs->hasPages())
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
                {{ $accessLogs->links('vendor.pagination.bordered-case') }}
            </div>
        @endif
    </div>

</div>
