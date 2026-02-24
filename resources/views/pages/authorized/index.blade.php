<?php

use App\Models\Authorized;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $activeOnly = false;

    public bool $showEditModal = false;

    public $editingAuthorizedId = null;

    public string $editUuid = '';

    public string $editFirstName = '';

    public string $editLastName = '';

    public string $editGroup = '';

    public string $editQuota = '';

    public bool $editIsActive = false;

    public bool $showDeleteModal = false;

    public $deletingAuthorizedId = null;

    public string $deleteUuid = '';

    public bool $showAddModal = false;

    public string $addUuid = '';

    public string $addFirstName = '';

    public string $addLastName = '';

    public string $addGroup = '';

    public string $addQuota = '';

    public bool $addIsActive = true;

    public string $addUuidSearch = '';

    public function with(): array
    {
        return [
            'authorizeds' => Authorized::query()
                ->when($this->search, function ($query) {
                    $query->whereFullText(['uuid', 'group', 'first_name', 'last_name'], $this->search.' * ', ['mode' => 'boolean']);
                })
                ->when($this->activeOnly, fn ($query) => $query->where('is_active', true))
                ->paginate(10),
            'unauthorizeds' => \App\Models\Unauthorized::when($this->addUuidSearch, function ($query) {
                    return $query->where('uuid', 'like', "{$this->addUuidSearch}%");
                })
                ->orderBy('created_at', 'desc')
                ->take(8)
                ->get(),
        ];
    }

    public function edit($id)
    {
        $authorized = Authorized::findOrFail($id);
        $this->editingAuthorizedId = $id;
        $this->editUuid = $authorized->uuid;
        $this->editFirstName = $authorized->first_name;
        $this->editLastName = $authorized->last_name;
        $this->editGroup = $authorized->group;
        $this->editQuota = $authorized->quota;
        $this->editIsActive = $authorized->is_active;
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingAuthorizedId = null;
    }

    public function update()
    {
        $this->validate([
            'editGroup' => 'required|in:merah,biru',
            'editQuota' => 'required|numeric',
            'editIsActive' => 'boolean',
        ]);

        try {
            $authorized = Authorized::findOrFail($this->editingAuthorizedId);
            $authorized->update([
                'group' => $this->editGroup,
                'quota' => $this->editQuota,
                'is_active' => $this->editIsActive,
            ]);

            $this->closeEditModal();
            $this->dispatch('notify', message: 'Authorized record updated successfully.', variant: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Failed to update authorized record. Please try again.', variant: 'danger');
        }
    }

    public function confirmDelete($id)
    {
        $authorized = Authorized::findOrFail($id);
        $this->deletingAuthorizedId = $id;
        $this->deleteUuid = $authorized->uuid;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingAuthorizedId = null;
        $this->deleteUuid = '';
    }

    public function destroy()
    {
        try {
            Authorized::findOrFail($this->deletingAuthorizedId)->delete();
            $this->closeDeleteModal();
            $this->dispatch('notify', message: 'Authorized record deleted successfully.', variant: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Failed to delete authorized record.', variant: 'danger');
        }
    }

    public function openAddModal()
    {
        $this->reset(['addUuid', 'addFirstName', 'addLastName', 'addGroup', 'addQuota', 'addUuidSearch']);
        $this->addIsActive = true;

        $unauthorized = \App\Models\Unauthorized::orderBy('created_at', 'desc')->first();
        if ($unauthorized) {
            $this->addUuid = $unauthorized->uuid;
        }

        $this->showAddModal = true;
    }

    public function closeAddModal()
    {
        $this->showAddModal = false;
        $this->reset(['addUuidSearch']);
    }

    public function store()
    {
        $this->validate([
            'addUuid' => 'required|exists:unauthorizeds,uuid|unique:authorizeds,uuid',
            'addFirstName' => 'required|string|max:255',
            'addLastName' => 'required|string|max:255',
            'addGroup' => 'required|in:merah,biru',
            'addQuota' => 'required|numeric',
            'addIsActive' => 'boolean',
        ]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                Authorized::create([
                    'uuid' => $this->addUuid,
                    'first_name' => $this->addFirstName,
                    'last_name' => $this->addLastName,
                    'group' => $this->addGroup,
                    'quota' => $this->addQuota,
                    'is_active' => $this->addIsActive,
                ]);

                \App\Models\Unauthorized::where('uuid', $this->addUuid)->delete();
            });

            $this->closeAddModal();
            $this->reset(['addUuid', 'addFirstName', 'addLastName', 'addGroup', 'addQuota', 'addUuidSearch']);
            $this->addIsActive = true;
            $this->dispatch('notify', message: 'Authorized record created successfully.', variant: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Failed to create authorized record. Try again.', variant: 'danger');
        }
    }
}; ?>

<x-slot name="title">Authorized</x-slot>

<div class="flex h-full w-full flex-1 flex-col gap-6">

    {{-- Page Header --}}
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="xl" level="1">Authorized List</flux:heading>
            <flux:subheading size="lg">Manage UUID authorization data for access control.</flux:subheading>
        </div>
        <div>
            <flux:button wire:click="openAddModal" variant="primary" icon="plus">Add Authorized</flux:button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:flex-row sm:items-center sm:justify-between">
        <flux:input
            wire:model.live="search"
            icon="magnifying-glass"
            placeholder="Search by UUID or Group..."
            class="w-full sm:max-w-xs"
        />
        <div class="flex items-center gap-2">
            <span class="text-sm text-zinc-500 dark:text-zinc-400">Show active only</span>
            <flux:switch wire:model.live="activeOnly" />
        </div>
    </div>

    {{-- Table Card --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">UUID</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Full Name</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Group</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Quota</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($authorizeds as $authorized)
                        <tr class="transition-colors duration-150 hover:bg-hover/20 dark:hover:bg-hover/30">
                            <td class="px-4 py-3.5 text-center">
                                <span class="font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $authorized->uuid }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $authorized->first_name }} {{ $authorized->last_name }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <flux:badge size="sm" :color="$authorized->group === 'merah' ? 'red' : 'blue'" inset="top bottom" class="w-20 justify-center">
                                    {{ ucfirst($authorized->group) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $authorized->quota }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <flux:badge size="sm" :color="$authorized->is_active ? 'green' : 'zinc'" inset="top bottom" class="w-24 justify-center" :icon="$authorized->is_active ? 'check-circle' : 'x-circle'">
                                    {{ $authorized->is_active ? 'Active' : 'Inactive' }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <flux:dropdown>
                                    <flux:button icon="ellipsis-horizontal" size="sm" variant="ghost" />
                                    <flux:menu>
                                        <flux:menu.item wire:click="edit({{ $authorized->id }})" icon="pencil">Edit</flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="confirmDelete({{ $authorized->id }})" icon="trash" variant="danger">Delete</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-400 dark:text-zinc-500">
                                No authorized records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($authorizeds->hasPages())
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
                {{ $authorizeds->links('vendor.pagination.bordered-case') }}
            </div>
        @endif
    </div>

    {{-- Edit Modal --}}
    <flux:modal name="edit-authorized" wire:model.live="showEditModal" variant="floating" class="md:w-120">
        <div class="space-y-5">
            <div class="border-b border-zinc-100 pb-4 dark:border-zinc-800">
                <flux:heading size="lg">Edit Authorized</flux:heading>
                <flux:subheading>Update quota, group, and active status.</flux:subheading>
            </div>

            {{-- UUID (readonly) --}}
            <flux:input
                label="UUID"
                value="{{ $editUuid }}"
                readonly
                disabled
                class="cursor-not-allowed opacity-70"
            />

            <div class="grid grid-cols-2 gap-4">
                <flux:input
                    label="First Name"
                    value="{{ $editFirstName }}"
                    readonly
                    disabled
                    class="cursor-not-allowed opacity-70"
                />

                <flux:input
                    label="Last Name"
                    value="{{ $editLastName }}"
                    readonly
                    disabled
                    class="cursor-not-allowed opacity-70"
                />
            </div>

            <flux:select wire:model="editGroup" label="Group" placeholder="Select group...">
                <option value="merah">Merah</option>
                <option value="biru">Biru</option>
            </flux:select>

            <flux:input wire:model="editQuota" label="Quota" type="number" />

            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                <div>
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Active Status</p>
                    <p class="text-xs text-zinc-400">Toggle whether this UUID is active.</p>
                </div>
                <flux:switch wire:model="editIsActive" />
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
    <flux:modal name="add-authorized" wire:model.live="showAddModal" variant="floating" class="md:w-120">
        <form wire:submit="store" class="space-y-5">
            <div class="border-b border-zinc-100 pb-4 dark:border-zinc-800">
                <flux:heading size="lg">Add Authorized</flux:heading>
                <flux:subheading>Authorize a new UUID from the unauthorized list.</flux:subheading>
            </div>

            @php
                $unauthOptions = $unauthorizeds->map(fn($u) => ['id' => $u->uuid, 'name' => $u->uuid])->toArray();
            @endphp
            <x-ui.searchable-select
                label="UUID"
                placeholder="Search unauthorized UUID..."
                wireModel="addUuid"
                searchWireModel="addUuidSearch"
                :options="$unauthOptions"
            />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="addFirstName" label="First Name" placeholder="John" />
                <flux:input wire:model="addLastName" label="Last Name" placeholder="Doe" />
            </div>

            <flux:select wire:model="addGroup" label="Group" placeholder="Select group...">
                <option value="merah">Merah</option>
                <option value="biru">Biru</option>
            </flux:select>

            <flux:input wire:model="addQuota" label="Quota" type="number" />

            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                <div>
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Active Status</p>
                    <p class="text-xs text-zinc-400">Toggle whether this UUID is active.</p>
                </div>
                <flux:switch wire:model="addIsActive" />
            </div>

            <div class="flex justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:button type="button" wire:click="closeAddModal">Cancel</flux:button>
                <flux:button type="submit" variant="primary">
                    <span wire:loading.remove wire:target="store">Add Authorized</span>
                    <span wire:loading wire:target="store">Saving...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="delete-authorized" wire:model.live="showDeleteModal" class="md:w-md">
        <div class="space-y-5">
            <div class="flex items-start gap-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon name="exclamation-triangle" class="size-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:heading size="lg">Delete Authorized</flux:heading>
                    <flux:subheading>This action cannot be undone.</flux:subheading>
                </div>
            </div>

            @if($deleteUuid)
            <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">You are about to delete:</p>
                <p class="mt-1 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $deleteUuid }}</p>
            </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:button wire:click="closeDeleteModal">Cancel</flux:button>
                <flux:button variant="danger" wire:click="destroy">Delete</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
