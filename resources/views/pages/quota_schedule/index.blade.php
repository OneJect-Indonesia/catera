<?php

use App\Models\Authorized;
use App\Models\QuotaSchedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $startDate = '';
    public string $endDate = '';

    public string $currentTab = 'pending';

    public bool $showEditModal = false;

    public $editingQuotaScheduleId = null;

    public string $editAuthorizedUuid = '';

    public string $editAuthorizedName = '';

    public int $addAddQuota = 0;

    public int $editAddQuota = 0;

    public string $editTargetDate = '';

    public string $editStatus = 'pending';

    public bool $showDeleteModal = false;

    public $deletingQuotaScheduleId = null;

    public string $deleteAuthorizedUuid = '';

    public bool $showAddModal = false;

    public string $addAuthorizedUuid = '';

    public string $addTargetDate = '';

    public string $addAuthorizedUuidSearch = '';

    public function mount(): void
    {
        $this->addTargetDate = \Carbon\Carbon::today()->toDateString();
    }

    public function setTab($tab): void
    {
        $this->currentTab = $tab;
        $this->resetPage(); // Reset pagination when switching tabs
    }

    public function updated($property): void
    {
        if (in_array($property, ['startDate', 'endDate'])) {
            $this->validate([
                'startDate' => 'nullable|date_format:Y-m-d',
                'endDate' => 'nullable|date_format:Y-m-d',
            ]);
        }

        if (in_array($property, ['search', 'startDate', 'endDate'])) {
            $this->resetPage();
        }
    }

    public function with(): array
    {
        Gate::authorize('viewAny', QuotaSchedule::class);

        return [
            'quotaSchedules' => QuotaSchedule::query()
                ->select('catera.quota_schedules.*')
                ->join('catera.authorizeds', 'catera.quota_schedules.authorized_uuid', '=', 'catera.authorizeds.uuid')
                ->join('portal_application.md_users', 'catera.authorizeds.user_id', '=', 'portal_application.md_users.id')
                ->with('authorized.user')
                ->where('catera.quota_schedules.status', $this->currentTab)
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('portal_application.md_users.first_name', 'ilike', "{$this->search}%")
                           ->orWhere('portal_application.md_users.last_name', 'ilike', "{$this->search}%")
                           ->orWhere('portal_application.md_users.nik', 'ilike', "{$this->search}%")
                           ->orWhere('catera.authorizeds.uuid', 'ilike', "{$this->search}%");
                    });
                })
                ->when($this->startDate, fn ($q) => $q->where('catera.quota_schedules.target_date', '>=', $this->startDate))
                ->when($this->endDate, fn ($q) => $q->where('catera.quota_schedules.target_date', '<=', $this->endDate))
                ->orderBy('catera.quota_schedules.target_date', 'asc')
                ->paginate(10),
            'availableAuthorizeds' => Authorized::query()
                ->select('catera.authorizeds.*')
                ->join('portal_application.md_users', 'catera.authorizeds.user_id', '=', 'portal_application.md_users.id')
                ->with('user')
                ->active()
                ->when($this->addAuthorizedUuidSearch, function ($query) {
                    $query->where(function ($q) {
                        $q->where('catera.authorizeds.uuid', 'ilike', "{$this->addAuthorizedUuidSearch}%")
                          ->orWhere('catera.authorizeds.group', 'ilike', "{$this->addAuthorizedUuidSearch}%")
                          ->orWhere('portal_application.md_users.first_name', 'ilike', "{$this->addAuthorizedUuidSearch}%")
                          ->orWhere('portal_application.md_users.last_name', 'ilike', "{$this->addAuthorizedUuidSearch}%");
                    });
                })
                ->take(8)
                ->get(),
        ];
    }

    public function edit($id): void
    {
        $quotaSchedule = QuotaSchedule::with('authorized.user')->findOrFail($id);

        Gate::authorize('update', $quotaSchedule);

        $this->editingQuotaScheduleId = $id;
        $this->editAuthorizedUuid = $quotaSchedule->authorized->uuid ?? '';
        $this->editAuthorizedName = trim(($quotaSchedule->authorized->user->first_name ?? '').' '.($quotaSchedule->authorized->user->last_name ?? ''));
        $this->editAddQuota = $quotaSchedule->add_quota;
        $this->editTargetDate = $quotaSchedule->target_date ? $quotaSchedule->target_date->toDateString() : '';
        $this->editStatus = $quotaSchedule->status;

        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingQuotaScheduleId = null;
    }

    public function update(): void
    {
        $this->validate([
            'editAddQuota' => 'required|integer|min:1',
            'editTargetDate' => 'required|date',
        ]);

        try {
            $quotaSchedule = QuotaSchedule::findOrFail($this->editingQuotaScheduleId);

            Gate::authorize('update', $quotaSchedule);

            $quotaSchedule->update([
                'add_quota' => $this->editAddQuota,
                'target_date' => $this->editTargetDate,
            ]);

            $this->closeEditModal();
            $this->dispatch('notify', message: 'Scheduled quota updated successfully.', variant: 'success');
        } catch (\Exception $e) {
            Log::error('Failed to update scheduled quota', [
                'error' => $e->getMessage(),
                'registered_id' => $this->editingQuotaScheduleId,
            ]);
            $this->dispatch('notify', message: 'Failed to update scheduled quota. Please try again.', variant: 'danger');
        }
    }

    public function confirmDelete($id): void
    {
        $quotaSchedule = QuotaSchedule::with('authorized')->findOrFail($id);

        Gate::authorize('delete', $quotaSchedule);

        $this->deletingQuotaScheduleId = $id;
        $this->deleteAuthorizedUuid = $quotaSchedule->authorized->uuid ?? 'Unknown';
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingQuotaScheduleId = null;
        $this->deleteAuthorizedUuid = '';
    }

    public function destroy(): void
    {
        try {
            $quotaSchedule = QuotaSchedule::findOrFail($this->deletingQuotaScheduleId);

            Gate::authorize('delete', $quotaSchedule);

            $quotaSchedule->delete();
            $this->closeDeleteModal();
            $this->dispatch('notify', message: 'Scheduled quota removed successfully.', variant: 'success');
        } catch (\Exception $e) {
            Log::error('Failed to delete scheduled quota', [
                'error' => $e->getMessage(),
                'registered_id' => $this->deletingQuotaScheduleId,
            ]);
            $this->dispatch('notify', message: 'Failed to complete the action.', variant: 'danger');
        }
    }

    public function openAddModal(): void
    {
        Gate::authorize('create', QuotaSchedule::class);

        $this->reset(['addAuthorizedUuid', 'addAuthorizedUuidSearch']);
        $this->addAddQuota = 1;
        $this->addTargetDate = \Carbon\Carbon::today()->toDateString();
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->reset(['addAuthorizedUuidSearch']);
    }

    public function store(): void
    {
        Gate::authorize('create', QuotaSchedule::class);

        $this->validate([
            'addAuthorizedUuid' => ['required', 'string', 'exists:authorizeds,uuid'],
            'addAddQuota' => ['required', 'integer', 'min:1'],
            'addTargetDate' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $hasDuplicate = QuotaSchedule::query()
            ->where('authorized_uuid', $this->addAuthorizedUuid)
            ->where('target_date', $this->addTargetDate)
            ->where('status', '!=', 'failed')
            ->exists();

        if ($hasDuplicate) {
            $this->dispatch('notify', message: 'A schedule already exists for this user on the selected date.', variant: 'danger');

            return;
        }

        try {
            QuotaSchedule::create([
                'authorized_uuid' => $this->addAuthorizedUuid,
                'add_quota' => $this->addAddQuota,
                'target_date' => $this->addTargetDate,
                'status' => 'pending',
            ]);

            $this->closeAddModal();
            $this->reset(['addAuthorizedUuid', 'addAuthorizedUuidSearch']);
            $this->addAddQuota = 1;

            $this->dispatch('notify', message: 'Scheduled quota setup successfully.', variant: 'success');
        } catch (\Exception $e) {
            Log::error('Failed to add scheduled quota', [
                'error' => $e->getMessage(),
                'uuid' => $this->addAuthorizedUuid,
            ]);
            $this->dispatch('notify', message: 'Failed to add scheduled quota. Try again.', variant: 'danger');
        }
    }
}; ?>

<x-slot name="title">Quota Schedules</x-slot>

<div class="flex h-full w-full flex-1 flex-col gap-6">

    {{-- Page Header --}}
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="xl" level="1">Scheduled Added Quotas</flux:heading>
            <flux:subheading size="lg">Manage automated quota additions for selected authorized users.</flux:subheading>
        </div>
        <div>
            @can('create', App\Models\QuotaSchedule::class)
                <flux:button wire:click="openAddModal" variant="primary" icon="plus">Add Schedule</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-4 text-sm font-medium text-center text-zinc-500 border-b border-zinc-200 dark:text-zinc-400 dark:border-zinc-700">
        <ul class="flex flex-wrap -mb-px">
            <li class="me-2">
                <button wire:click="setTab('pending')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $currentTab === 'pending' ? 'text-yellow-600 border-yellow-600 dark:text-yellow-500 dark:border-yellow-500' : 'border-transparent hover:text-zinc-600 hover:border-zinc-300 dark:hover:text-zinc-300' }}">Pending Schedule</button>
            </li>
            <li class="me-2">
                <button wire:click="setTab('success')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $currentTab === 'success' ? 'text-green-600 border-green-600 dark:text-green-500 dark:border-green-500' : 'border-transparent hover:text-zinc-600 hover:border-zinc-300 dark:hover:text-zinc-300' }}" aria-current="page">Done</button>
            </li>
        </ul>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" x-data="{ showFilters: false }">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search by User..."
                class="w-full sm:max-w-xs"
            />
            @php
                $activeFilterCount = collect([$startDate, $endDate])->filter()->count();
            @endphp
            <flux:button @click="showFilters = !showFilters" icon="funnel" :variant="$activeFilterCount > 0 ? 'primary' : 'filled'">
                Filter @if($activeFilterCount > 0) ({{ $activeFilterCount }}) @endif
            </flux:button>
        </div>

        <div x-show="showFilters" x-transition class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
            {{-- Start Date --}}
            <flux:input type="date" wire:model.live="startDate" label="Start Date" />

            {{-- End Date --}}
            <flux:input type="date" wire:model.live="endDate" label="End Date" />

            {{-- Reset Button --}}
            <div class="col-span-1 sm:col-span-2 flex justify-end gap-2 mt-2">
                <flux:button size="sm" wire:click="$set('startDate', ''); $set('endDate', '');" variant="ghost">Reset Filters</flux:button>
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
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Target Date</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Quota to Add</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        @if(auth()->user()->hasAnyPermission(['catera:quota_scheduling:update', 'catera:quota_scheduling:delete']))
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($quotaSchedules as $quotaSchedule)
                        <tr class="transition-colors duration-150 hover:bg-hover/20 dark:hover:bg-hover/30" wire:key="quota-schedule-{{ $quotaSchedule->id }}">
                            <td class="px-4 py-3.5 text-center">
                                <span class="font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $quotaSchedule->authorized->uuid ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $quotaSchedule->authorized->user->first_name ?? '' }} {{ $quotaSchedule->authorized->user->last_name ?? '' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $quotaSchedule->target_date ? $quotaSchedule->target_date->format('d M Y') : 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-sm font-bold text-green-600 dark:text-green-400">+{{ $quotaSchedule->add_quota }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <flux:badge size="sm" :color="$quotaSchedule->status === 'success' ? 'green' : 'yellow'" inset="top bottom" class="w-24 justify-center" :icon="$quotaSchedule->status === 'success' ? 'check-circle' : 'clock'">
                                    {{ ucfirst($quotaSchedule->status) }}
                                </flux:badge>
                            </td>
                            @if(auth()->user()->can('update', $quotaSchedule) || auth()->user()->can('delete', $quotaSchedule))
                            <td class="px-4 py-3.5 text-center">
                                <flux:dropdown>
                                    <flux:button icon="ellipsis-horizontal" size="sm" variant="ghost" />
                                    <flux:menu>
                                        @if($quotaSchedule->status === 'pending')
                                            @can('update', $quotaSchedule)
                                                <flux:menu.item wire:click="edit({{ $quotaSchedule->id }})" icon="pencil">Edit</flux:menu.item>
                                            @endcan
                                        @endif
                                        @can('delete', $quotaSchedule)
                                            @if($quotaSchedule->status === 'pending')
                                                <flux:menu.separator />
                                            @endif
                                            <flux:menu.item wire:click="confirmDelete({{ $quotaSchedule->id }})" icon="trash" variant="danger">Remove</flux:menu.item>
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-400 dark:text-zinc-500">
                                No scheduled quotas found in this tab.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($quotaSchedules->hasPages())
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
                {{ $quotaSchedules->links('vendor.pagination.bordered-case') }}
            </div>
        @endif
    </div>

    {{-- Edit Modal --}}
    <flux:modal name="edit-quota-schedule" wire:model.live="showEditModal" variant="floating" class="md:w-120">
        <div class="space-y-5">
            <div class="border-b border-zinc-100 pb-4 dark:border-zinc-800">
                <flux:heading size="lg">Edit Scheduled Quota</flux:heading>
                <flux:subheading>Update the amount of quota scheduled to be added to this user.</flux:subheading>
            </div>

            {{-- UUID and Name (readonly) --}}
            <div class="grid grid-cols-2 gap-4">
                <flux:input
                    label="User UUID"
                    value="{{ $editAuthorizedUuid }}"
                    readonly
                    disabled
                    class="cursor-not-allowed opacity-70"
                />
                <flux:input
                    label="User Name"
                    value="{{ $editAuthorizedName }}"
                    readonly
                    disabled
                    class="cursor-not-allowed opacity-70"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="editAddQuota" label="Quota to Add" type="number" min="1" />
                <flux:input wire:model="editTargetDate" label="Target Date" type="date" />
            </div>

            <div class="flex justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:button wire:click="closeEditModal">Cancel</flux:button>
                <flux:button variant="primary" wire:click="update">
                    <span wire:loading.remove wire:target="update">Save Changes</span>
                    <span wire:loading wire:target="update">Saving...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Add Modal --}}
    <flux:modal name="add-quota-schedule" wire:model.live="showAddModal" variant="floating" class="md:w-120">
        <form wire:submit="store" class="space-y-5">
            <div class="border-b border-zinc-100 pb-4 dark:border-zinc-800">
                <flux:heading size="lg">Create Quota Schedule</flux:heading>
                <flux:subheading>Select an authorized user to receive additional daily quota.</flux:subheading>
            </div>

            @php
                $availOptions = $availableAuthorizeds->map(function($auth) {
                    $name = trim($auth->user->first_name . ' ' . $auth->user->last_name);
                    $nik = $auth->user->nik ?? 'N/A';
                    return [
                        'id' => $auth->uuid,
                        'name' => "{$name} - {$nik}"
                    ];
                })->toArray();
            @endphp
            <x-ui.searchable-select
                label="User (UUID)"
                placeholder="Select an authorized user..."
                wireModel="addAuthorizedUuid"
                searchWireModel="addAuthorizedUuidSearch"
                :options="$availOptions"
            />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="addAddQuota" label="Quota to Add" type="number" min="1" />
                <flux:input wire:model="addTargetDate" label="Target Date" type="date" min="{{ now()->toDateString() }}" />
            </div>

            <div class="flex justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:button type="button" wire:click="closeAddModal">Cancel</flux:button>
                <flux:button type="submit" variant="primary">
                    <span wire:loading.remove wire:target="store">Add Schedule</span>
                    <span wire:loading wire:target="store">Saving...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="delete-quota-schedule" wire:model.live="showDeleteModal" class="md:w-md">
        <div class="space-y-5">
            <div class="flex items-start gap-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon name="exclamation-triangle" class="size-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:heading size="lg">Remove Quota Schedule</flux:heading>
                    <flux:subheading>This action cannot be undone.</flux:subheading>
                </div>
            </div>

            @if($deleteAuthorizedUuid)
            <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Removing quota addition schedule for:</p>
                <p class="mt-1 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $deleteAuthorizedUuid }}</p>
            </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:button wire:click="closeDeleteModal">Cancel</flux:button>
                <flux:button variant="danger" wire:click="destroy">Remove Schedule</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
