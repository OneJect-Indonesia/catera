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
    public string $tempStartDate = '';
    public string $tempEndDate = '';
    public bool $showFilterModal = false;

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

    public string $deleteAuthorizedName = '';

    public bool $showAddModal = false;

    public string $addAuthorizedUuid = '';

    public array $addSelectedUuids = [];

    public array $addSelectedUsers = [];

    public array $addSkippedUsers = [];

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
        if ($property === 'search') {
            $this->resetPage();
        }
    }

    public function openFilterModal(): void
    {
        $this->tempStartDate = $this->startDate;
        $this->tempEndDate = $this->endDate;
        $this->showFilterModal = true;
    }

    public function applyFilters(): void
    {
        $this->validate([
            'tempStartDate' => 'nullable|date_format:Y-m-d',
            'tempEndDate' => 'nullable|date_format:Y-m-d',
        ]);

        $this->startDate = $this->tempStartDate;
        $this->endDate = $this->tempEndDate;
        $this->resetPage();
        $this->showFilterModal = false;
    }

    public function resetFilters(): void
    {
        $this->reset(['startDate', 'endDate', 'tempStartDate', 'tempEndDate']);
        $this->resetPage();
        $this->showFilterModal = false;
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
                          ->orWhereHas('mdGroup', function ($gQuery) {
                              $gQuery->where('nama_group', 'ilike', "{$this->addAuthorizedUuidSearch}%");
                          })
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
        $quotaSchedule = QuotaSchedule::with('authorized.user')->findOrFail($id);

        Gate::authorize('delete', $quotaSchedule);

        $this->deletingQuotaScheduleId = $id;
        $this->deleteAuthorizedUuid = $quotaSchedule->authorized->uuid ?? 'Unknown';
        $this->deleteAuthorizedName = trim(($quotaSchedule->authorized->user->first_name ?? '').' '.($quotaSchedule->authorized->user->last_name ?? ''));
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingQuotaScheduleId = null;
        $this->deleteAuthorizedUuid = '';
        $this->deleteAuthorizedName = '';
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

        $this->reset(['addAuthorizedUuid', 'addAuthorizedUuidSearch', 'addSelectedUuids', 'addSelectedUsers', 'addSkippedUsers']);
        $this->addAddQuota = 1;
        $this->addTargetDate = \Carbon\Carbon::today()->toDateString();
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->reset(['addAuthorizedUuidSearch', 'addSelectedUuids', 'addSelectedUsers', 'addSkippedUsers']);
    }

    public function updatedAddAuthorizedUuid($value): void
    {
        if ($value) {
            $authorized = Authorized::with('user')->where('uuid', $value)->first();
            if ($authorized) {
                $name = trim(($authorized->user->first_name ?? '').' '.($authorized->user->last_name ?? ''));
                $nik = $authorized->user->nik ?? 'N/A';

                if (!in_array($value, $this->addSelectedUuids)) {
                    $this->addSelectedUuids[] = $value;
                    $this->addSelectedUsers[] = [
                        'uuid' => $value,
                        'name' => "{$name} ({$nik})",
                    ];
                }
            }
            $this->addAuthorizedUuid = '';
            $this->addAuthorizedUuidSearch = '';
        }
    }

    public function removeSelectedUser($uuid): void
    {
        $this->addSelectedUuids = array_values(array_diff($this->addSelectedUuids, [$uuid]));
        $this->addSelectedUsers = array_values(array_filter($this->addSelectedUsers, fn($u) => $u['uuid'] !== $uuid));
    }

    public function store(): void
    {
        Gate::authorize('create', QuotaSchedule::class);

        $this->validate([
            'addSelectedUuids' => ['required', 'array', 'min:1'],
            'addSelectedUuids.*' => ['required', 'string', 'exists:authorizeds,uuid'],
            'addAddQuota' => ['required', 'integer', 'min:1'],
            'addTargetDate' => ['required', 'date', 'after_or_equal:today'],
        ], [
            'addSelectedUuids.required' => 'Please select at least one user.',
        ]);

        $this->addSkippedUsers = [];
        $insertedCount = 0;

        foreach ($this->addSelectedUuids as $uuid) {
            $authorized = Authorized::with('user')->where('uuid', $uuid)->first();
            $name = $authorized ? trim(($authorized->user->first_name ?? '').' '.($authorized->user->last_name ?? '')) : 'Unknown';
            $nik = $authorized ? ($authorized->user->nik ?? 'N/A') : 'N/A';

            $hasDuplicate = QuotaSchedule::query()
                ->where('authorized_uuid', $uuid)
                ->where('target_date', $this->addTargetDate)
                ->where('status', '!=', 'failed')
                ->exists();

            if ($hasDuplicate) {
                $this->addSkippedUsers[] = "{$name} ({$nik})";
                continue;
            }

            try {
                QuotaSchedule::create([
                    'authorized_uuid' => $uuid,
                    'add_quota' => $this->addAddQuota,
                    'target_date' => $this->addTargetDate,
                    'status' => 'pending',
                ]);
                $insertedCount++;
            } catch (\Exception $e) {
                Log::error('Failed to add scheduled quota', [
                    'error' => $e->getMessage(),
                    'uuid' => $uuid,
                ]);
                $this->addSkippedUsers[] = "{$name} ({$nik}) [Failed]";
            }
        }

        if (count($this->addSkippedUsers) > 0) {
            $this->addSelectedUuids = array_values(array_filter($this->addSelectedUuids, function($uuid) {
                $authorized = Authorized::with('user')->where('uuid', $uuid)->first();
                $name = $authorized ? trim(($authorized->user->first_name ?? '').' '.($authorized->user->last_name ?? '')) : 'Unknown';
                $nik = $authorized ? ($authorized->user->nik ?? 'N/A') : 'N/A';
                return in_array("{$name} ({$nik})", $this->addSkippedUsers) || in_array("{$name} ({$nik}) [Failed]", $this->addSkippedUsers);
            }));

            $this->addSelectedUsers = array_values(array_filter($this->addSelectedUsers, function($user) {
                return in_array($user['uuid'], $this->addSelectedUuids);
            }));

            if ($insertedCount > 0) {
                $this->dispatch('notify', message: "Successfully scheduled quota for {$insertedCount} user(s). Some were skipped due to duplicates/errors.", variant: 'warning');
            } else {
                $this->dispatch('notify', message: 'Failed to schedule quota. See details in modal.', variant: 'danger');
            }
        } else {
            $this->closeAddModal();
            $this->reset(['addSelectedUuids', 'addSelectedUsers', 'addAuthorizedUuid', 'addAuthorizedUuidSearch']);
            $this->addAddQuota = 1;
            $this->dispatch('notify', message: 'All scheduled quotas setup successfully.', variant: 'success');
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
    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:flex-row sm:items-center sm:justify-between">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Search by User..."
            class="w-full sm:max-w-xs"
        />
        @php
            $activeFilterCount = collect([$startDate, $endDate])->filter()->count();
        @endphp
        <flux:button wire:click="openFilterModal" icon="funnel" :variant="$activeFilterCount > 0 ? 'primary' : 'filled'">
            Filters
        </flux:button>
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
                <flux:subheading>Select authorized users to receive additional daily quota.</flux:subheading>
            </div>

            @if(count($addSkippedUsers) > 0)
                <div class="rounded-lg bg-red-50 p-4 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                    <div class="flex items-start gap-3">
                        <flux:icon name="exclamation-triangle" class="size-5 text-red-500 dark:text-red-400 mt-0.5 shrink-0" />
                        <div>
                            <p class="text-sm font-medium text-red-800 dark:text-red-300">Some users were skipped</p>
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1">The following users already have a schedule for this date or failed to insert:</p>
                            <ul class="mt-2 space-y-1">
                                @foreach($addSkippedUsers as $skipped)
                                    <li class="text-xs text-red-700 dark:text-red-300">- {{ $skipped }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

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
                label="Add Users"
                placeholder="Search and select users..."
                wireModel="addAuthorizedUuid"
                searchWireModel="addAuthorizedUuidSearch"
                :options="$availOptions"
            />

            @if(count($addSelectedUsers) > 0)
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Selected Users ({{ count($addSelectedUsers) }})</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($addSelectedUsers as $user)
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-primary-100 px-2.5 py-1 text-xs font-medium text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                                {{ $user['name'] }}
                                <button type="button" wire:click="removeSelectedUser('{{ $user['uuid'] }}')" class="group relative -mr-1 h-4 w-4 rounded-sm hover:bg-primary-200 dark:hover:bg-primary-800 flex items-center justify-center">
                                    <span class="sr-only">Remove</span>
                                    <flux:icon name="x-mark" class="h-3 w-3 text-primary-400 group-hover:text-primary-600 dark:group-hover:text-primary-200" />
                                </button>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="addAddQuota" label="Quota to Add" type="number" min="1" />
                <flux:input wire:model="addTargetDate" label="Target Date" type="date" min="{{ now()->toDateString() }}" />
            </div>

            <div class="flex justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:button type="button" wire:click="closeAddModal">Cancel</flux:button>
                <flux:button type="submit" variant="primary" :disabled="count($addSelectedUuids) === 0">
                    <span wire:loading.remove wire:target="store">Add Schedule ({{ count($addSelectedUuids) }} user(s))</span>
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

            @if($deleteAuthorizedName)
            <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Removing quota addition schedule for:</p>
                <p class="mt-1 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $deleteAuthorizedName }}</p>
            </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:button wire:click="closeDeleteModal">Cancel</flux:button>
                <flux:button variant="danger" wire:click="destroy">Remove Schedule</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Filter Modal --}}
    <flux:modal name="quota-schedule-filters" wire:model.live="showFilterModal" variant="floating" class="md:w-120">
        <div class="space-y-5">
            <div class="border-b border-zinc-100 pb-4 dark:border-zinc-800">
                <flux:heading size="lg">Filters</flux:heading>
                <flux:subheading>Refine your search results by date range.</flux:subheading>
            </div>

            <flux:input type="date" wire:model="tempStartDate" label="Start Date" />

            <flux:input type="date" wire:model="tempEndDate" label="End Date" />

            <div class="flex justify-between gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:button wire:click="resetFilters" variant="ghost">Reset</flux:button>
                <flux:button variant="primary" wire:click="applyFilters">Apply Filters</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
