<?php

use App\Models\Authorized;
use App\Models\Registered;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $currentTab = 'pending';

    public bool $showEditModal = false;

    public $editingRegisteredId = null;

    public string $editAuthorizedUuid = '';

    public string $editAuthorizedName = '';

    public int $editAddQuota = 0;

    public string $editTargetDate = '';

    public string $editStatus = 'pending';

    public bool $showDeleteModal = false;

    public $deletingRegisteredId = null;

    public string $deleteAuthorizedUuid = '';

    public bool $showAddModal = false;

    public string $addAuthorizedUuid = '';

    public int $addAddQuota = 1;

    public string $addTargetDate = '';

    public function mount()
    {
        $this->addTargetDate = \Carbon\Carbon::today()->toDateString();
    }

    public function setTab($tab)
    {
        $this->currentTab = $tab;
        $this->resetPage(); // Reset pagination when switching tabs
    }

    public function with(): array
    {
        return [
            'registereds' => Registered::query()
                ->with('authorized')
                ->where('status', $this->currentTab)
                ->when($this->search, function ($query) {
                    $query->whereHas('authorized', function ($q) {
                        $q->where('uuid', 'like', "%{$this->search}%")
                            ->orWhere('first_name', 'like', "%{$this->search}%")
                            ->orWhere('last_name', 'like', "%{$this->search}%");
                    });
                })
                ->orderBy('target_date', 'asc')
                ->paginate(10),
            'availableAuthorizeds' => Authorized::query()
                ->active()
                ->get(),
        ];
    }

    public function edit($id)
    {
        $registered = Registered::with('authorized')->findOrFail($id);
        $this->editingRegisteredId = $id;
        $this->editAuthorizedUuid = $registered->authorized->uuid ?? '';
        $this->editAuthorizedName = trim(($registered->authorized->first_name ?? '').' '.($registered->authorized->last_name ?? ''));
        $this->editAddQuota = $registered->add_quota;
        $this->editTargetDate = $registered->target_date ? $registered->target_date->toDateString() : '';
        $this->editStatus = $registered->status;

        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingRegisteredId = null;
    }

    public function update()
    {
        $this->validate([
            'editAddQuota' => 'required|integer|min:1',
            'editTargetDate' => 'required|date',
        ]);

        try {
            $registered = Registered::findOrFail($this->editingRegisteredId);
            $registered->update([
                'add_quota' => $this->editAddQuota,
                'target_date' => $this->editTargetDate,
            ]);

            $this->closeEditModal();
            $this->dispatch('notify', message: 'Scheduled quota updated successfully.', variant: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Failed to update scheduled quota. Please try again.', variant: 'danger');
        }
    }

    public function confirmDelete($id)
    {
        $registered = Registered::with('authorized')->findOrFail($id);
        $this->deletingRegisteredId = $id;
        $this->deleteAuthorizedUuid = $registered->authorized->uuid ?? 'Unknown';
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingRegisteredId = null;
        $this->deleteAuthorizedUuid = '';
    }

    public function destroy()
    {
        try {
            Registered::findOrFail($this->deletingRegisteredId)->delete();
            $this->closeDeleteModal();
            $this->dispatch('notify', message: 'Scheduled quota removed successfully.', variant: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Failed to complete the action.', variant: 'danger');
        }
    }

    public function openAddModal()
    {
        $this->reset(['addAuthorizedUuid']);
        $this->addAddQuota = 1;
        $this->addTargetDate = \Carbon\Carbon::today()->toDateString();
        $this->showAddModal = true;
    }

    public function closeAddModal()
    {
        $this->showAddModal = false;
    }

    public function store()
    {
        $this->validate([
            'addAuthorizedUuid' => ['required', 'string', 'exists:authorizeds,uuid'],
            'addAddQuota' => ['required', 'integer', 'min:1'],
            'addTargetDate' => ['required', 'date'],
        ]);

        try {
            Registered::create([
                'authorized_uuid' => $this->addAuthorizedUuid,
                'add_quota' => $this->addAddQuota,
                'target_date' => $this->addTargetDate,
                'status' => 'pending',
            ]);

            $this->closeAddModal();
            $this->reset(['addAuthorizedUuid']);
            $this->addAddQuota = 1;

            $this->dispatch('notify', message: 'Scheduled quota setup successfully.', variant: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Failed to add scheduled quota. Try again.', variant: 'danger');
        }
    }
}; ?>

<x-slot name="title">Scheduled Quota (Registered)</x-slot>

<div class="flex h-full w-full flex-1 flex-col gap-6">

    {{-- Page Header --}}
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="xl" level="1">Scheduled Added Quotas</flux:heading>
            <flux:subheading size="lg">Manage automated quota additions for selected authorized users.</flux:subheading>
        </div>
        <div>
            <flux:button wire:click="openAddModal" variant="primary" icon="plus">Add Schedule</flux:button>
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
            wire:model.live="search"
            icon="magnifying-glass"
            placeholder="Search by User..."
            class="w-full sm:max-w-xs"
        />
    </div>

    {{-- Table Card --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">UUID</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Full Name</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Target Date</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Quota to Add</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($registereds as $registered)
                        <tr class="transition-colors duration-150 hover:bg-hover/20 dark:hover:bg-hover/30">
                            <td class="px-4 py-3.5 text-center">
                                <span class="font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $registered->authorized->uuid ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $registered->authorized->first_name ?? '' }} {{ $registered->authorized->last_name ?? '' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $registered->target_date ? $registered->target_date->format('d M Y') : 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-sm font-bold text-green-600 dark:text-green-400">+{{ $registered->add_quota }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <flux:badge size="sm" :color="$registered->status === 'success' ? 'green' : 'yellow'" inset="top bottom" class="w-24 justify-center" :icon="$registered->status === 'success' ? 'check-circle' : 'clock'">
                                    {{ ucfirst($registered->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <flux:dropdown>
                                    <flux:button icon="ellipsis-horizontal" size="sm" variant="ghost" />
                                    <flux:menu>
                                        @if($registered->status === 'pending')
                                            <flux:menu.item wire:click="edit({{ $registered->id }})" icon="pencil">Edit</flux:menu.item>
                                            <flux:menu.separator />
                                        @endif
                                        <flux:menu.item wire:click="confirmDelete({{ $registered->id }})" icon="trash" variant="danger">Remove</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
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

        @if ($registereds->hasPages())
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
                {{ $registereds->links('vendor.pagination.bordered-case') }}
            </div>
        @endif
    </div>

    {{-- Edit Modal --}}
    <flux:modal name="edit-registered" wire:model.live="showEditModal" variant="floating" class="md:w-120">
        <div class="space-y-5">
            <div class="border-b border-zinc-100 pb-4 dark:border-zinc-800">
                <flux:heading size="lg">Edit Scheduled Quota</flux:heading>
                <flux:subheading>Update the amount of quota added to this user daily.</flux:subheading>
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
    <flux:modal name="add-registered" wire:model.live="showAddModal" variant="floating" class="md:w-120">
        <form wire:submit="store" class="space-y-5">
            <div class="border-b border-zinc-100 pb-4 dark:border-zinc-800">
                <flux:heading size="lg">Create Quota Schedule</flux:heading>
                <flux:subheading>Select an authorized user to receive additional daily quota.</flux:subheading>
            </div>

            @php
                $availOptions = $availableAuthorizeds->map(function($auth) {
                    $name = trim($auth->first_name . ' ' . $auth->last_name);
                    $group = ucfirst($auth->group);
                    return [
                        'id' => $auth->uuid,
                        'name' => "{$name} - {$group}"
                    ];
                })->toArray();
            @endphp
            <x-ui.searchable-select
                label="User (UUID)"
                placeholder="Select an authorized user..."
                wireModel="addAuthorizedUuid"
                :options="$availOptions"
            />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="addAddQuota" label="Quota to Add" type="number" min="1" />
                <flux:input wire:model="addTargetDate" label="Target Date" type="date" />
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
    <flux:modal name="delete-registered" wire:model.live="showDeleteModal" class="md:w-md">
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
