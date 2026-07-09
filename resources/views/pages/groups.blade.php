<?php

use App\Http\Requests\StoreMdGroupRequest;
use App\Http\Requests\UpdateMdGroupRequest;
use App\Models\MdGroup;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showEditModal = false;

    public ?int $editingGroupId = null;

    public string $editNamaGroup = '';

    public string $editShortDescription = '';

    public bool $showDeleteModal = false;

    public ?int $deletingGroupId = null;

    public string $deleteName = '';

    public bool $showAddModal = false;

    public string $addNamaGroup = '';

    public string $addShortDescription = '';

    public function with(): array
    {
        Gate::authorize('viewAny', MdGroup::class);

        return [
            'groups' => MdGroup::query()
                ->when($this->search, function ($query) {
                    $query->where('nama_group', 'ilike', $this->search . '%')
                          ->orWhere('short_description', 'ilike', $this->search . '%');
                })
                ->paginate(10),
        ];
    }

    public function edit($id): void
    {
        $group = MdGroup::findOrFail($id);

        Gate::authorize('update', $group);

        $this->editingGroupId = $id;
        $this->editNamaGroup = $group->nama_group;
        $this->editShortDescription = $group->short_description ?? '';
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingGroupId = null;
    }

    public function update(): void
    {
        $group = MdGroup::findOrFail($this->editingGroupId);

        Gate::authorize('update', $group);

        $this->validate((new UpdateMdGroupRequest(['editingGroupId' => $this->editingGroupId]))->rules());

        try {
            $group->update([
                'nama_group' => $this->editNamaGroup,
                'short_description' => $this->editShortDescription,
            ]);

            $this->closeEditModal();
            $this->dispatch('notify', message: 'Group updated successfully.', variant: 'success');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to update group', [
                'error' => $e->getMessage(),
                'group_id' => $this->editingGroupId,
            ]);
            $this->dispatch('notify', message: 'Failed to update group. Please try again.', variant: 'danger');
        }
    }

    public function confirmDelete($id): void
    {
        $group = MdGroup::findOrFail($id);

        Gate::authorize('delete', $group);

        $this->deletingGroupId = $id;
        $this->deleteName = $group->nama_group;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingGroupId = null;
        $this->deleteName = '';
    }

    public function destroy(): void
    {
        try {
            $group = MdGroup::findOrFail($this->deletingGroupId);

            Gate::authorize('delete', $group);

            $group->delete();
            $this->closeDeleteModal();
            $this->dispatch('notify', message: 'Group deleted successfully.', variant: 'success');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to delete group', [
                'error' => $e->getMessage(),
                'group_id' => $this->deletingGroupId,
            ]);
            $this->dispatch('notify', message: 'Failed to delete group.', variant: 'danger');
        }
    }

    public function openAddModal(): void
    {
        Gate::authorize('create', MdGroup::class);

        $this->reset(['addNamaGroup', 'addShortDescription']);
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->reset(['addNamaGroup', 'addShortDescription']);
    }

    public function store(): void
    {
        Gate::authorize('create', MdGroup::class);

        $this->validate((new StoreMdGroupRequest())->rules());

        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                MdGroup::create([
                    'nama_group' => $this->addNamaGroup,
                    'short_description' => $this->addShortDescription,
                ]);
            });

            $this->closeAddModal();
            $this->dispatch('notify', message: 'Group created successfully.', variant: 'success');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create group', [
                'error' => $e->getMessage(),
                'nama_group' => $this->addNamaGroup,
            ]);
            $this->dispatch('notify', message: 'Failed to create group. Try again.', variant: 'danger');
        }
    }
}; ?>

<x-slot name="title">Groups</x-slot>

<div class="flex h-full w-full flex-1 flex-col gap-6">

    {{-- Page Header --}}
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="xl" level="1">Master Groups</flux:heading>
            <flux:subheading size="lg">Manage groups and color configurations for authorization control.</flux:subheading>
        </div>
        @can('create', App\Models\MdGroup::class)
            <div>
                <flux:button wire:click="openAddModal" variant="primary" icon="plus">Add Group</flux:button>
            </div>
        @endcan
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:flex-row sm:items-center sm:justify-between">
        <flux:input
            wire:model.live.debounce.500ms="search"
            icon="magnifying-glass"
            placeholder="Search by name or description..."
            class="w-full sm:max-w-xs"
        />
    </div>

    {{-- Table Card --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Group Name</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Short Description</th>
                        @if(auth()->user()->hasAnyPermission(['catera:md_group:update', 'catera:md_group:delete']))
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($groups as $group)
                        <tr class="transition-colors duration-150 hover:bg-hover/20 dark:hover:bg-hover/30" wire:key="group-{{ $group->id }}">
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $group->nama_group }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $group->short_description ?? '-' }}</span>
                            </td>
                            @if(auth()->user()->can('update', $group) || auth()->user()->can('delete', $group))
                                <td class="px-4 py-3.5 text-center">
                                    <flux:dropdown>
                                        <flux:button icon="ellipsis-horizontal" size="sm" variant="ghost" />
                                        <flux:menu>
                                            @can('update', $group)
                                                <flux:menu.item wire:click="edit({{ $group->id }})" icon="pencil">Edit</flux:menu.item>
                                            @endcan

                                            @if(auth()->user()->can('update', $group) && auth()->user()->can('delete', $group))
                                                <flux:menu.separator />
                                            @endif

                                            @can('delete', $group)
                                                <flux:menu.item wire:click="confirmDelete({{ $group->id }})" icon="trash" variant="danger">Delete</flux:menu.item>
                                            @endcan
                                        </flux:menu>
                                    </flux:dropdown>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-center text-sm text-zinc-400 dark:text-zinc-500">
                                No groups found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($groups->hasPages())
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
                {{ $groups->links('vendor.pagination.bordered-case') }}
            </div>
        @endif
    </div>

    {{-- Edit Modal --}}
    <flux:modal name="edit-group" wire:model.live="showEditModal" variant="floating" class="md:w-120">
        <div class="space-y-5">
            <div class="border-b border-zinc-100 pb-4 dark:border-zinc-800">
                <flux:heading size="lg">Edit Group</flux:heading>
                <flux:subheading>Update group details.</flux:subheading>
            </div>

            <flux:input wire:model="editNamaGroup" label="Group Name" />

            <flux:input wire:model="editShortDescription" label="Short Description" />

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
    <flux:modal name="add-group" wire:model.live="showAddModal" variant="floating" class="md:w-120">
        <form wire:submit="store" class="space-y-5">
            <div class="border-b border-zinc-100 pb-4 dark:border-zinc-800">
                <flux:heading size="lg">Add Group</flux:heading>
                <flux:subheading>Create a new group in the database.</flux:subheading>
            </div>

            <flux:input wire:model="addNamaGroup" label="Group Name" placeholder="e.g. merah, biru, etc." />

            <flux:input wire:model="addShortDescription" label="Short Description" placeholder="e.g. Group Merah" />

            <div class="flex justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:button type="button" wire:click="closeAddModal">Cancel</flux:button>
                <flux:button type="submit" variant="primary">
                    <span wire:loading.remove wire:target="store">Add Group</span>
                    <span wire:loading wire:target="store">Saving...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="delete-group" wire:model.live="showDeleteModal" class="md:w-md">
        <div class="space-y-5">
            <div class="flex items-start gap-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon name="exclamation-triangle" class="size-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:heading size="lg">Delete Group</flux:heading>
                    <flux:subheading>This action cannot be undone.</flux:subheading>
                </div>
            </div>

            @if($deleteName)
            <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">You are about to delete:</p>
                <p class="mt-1 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $deleteName }}</p>
            </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:button wire:click="closeDeleteModal">Cancel</flux:button>
                <flux:button variant="danger" wire:click="destroy">Delete</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
