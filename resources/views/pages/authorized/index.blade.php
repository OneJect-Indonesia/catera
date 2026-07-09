<?php

use App\Http\Requests\StoreAuthorizedRequest;
use App\Http\Requests\UpdateAuthorizedRequest;
use App\Models\Authorized;
use App\Models\User;
use App\Models\MdGroup;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterGroup = '';

    public bool $activeOnly = false;

    public bool $showEditModal = false;

    public ?int $editingAuthorizedId = null;

    public string $editUuid = '';

    public ?int $editGroupId = null;

    public string $editQuota = '';

    // Read-only display fields for the edit modal (sourced from relationship)
    public string $editDisplayName = '';

    public string $editDisplayNik = '';

    public bool $showDeleteModal = false;

    public ?int $deletingAuthorizedId = null;

    public string $deleteName = '';

    public bool $showAddModal = false;

    public ?int $addUserId = null;

    public string $addUserSearch = '';

    public ?int $addGroupId = null;

    public string $addQuota = '';

    public string $addUuid = '';

    public string $addUuidSearch = '';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'filterGroup', 'activeOnly'])) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['filterGroup', 'activeOnly']);
        $this->resetPage();
    }

    public function with(): array
    {
        Gate::authorize('viewAny', Authorized::class);

        return [
            'authorizeds' => Authorized::query()
                ->with(['user', 'mdGroup'])
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('uuid', 'ilike', $this->search . '%')
                          ->orWhereHas('mdGroup', function ($gQuery) {
                              $gQuery->where('nama_group', 'ilike', $this->search . '%');
                          })
                          ->orWhereHas('user', function ($userQuery) {
                              $userQuery->where('first_name', 'ilike', $this->search . '%')
                                        ->orWhere('last_name', 'ilike', $this->search . '%')
                                        ->orWhere('nik', 'ilike', $this->search . '%');
                          });
                    });
                })
                ->when($this->filterGroup, function ($query) {
                    $query->where('group_id', $this->filterGroup);
                })
                ->when($this->activeOnly, function ($query) {
                    $query->whereHas('user', function ($q) {
                        $q->where('status', 'active');
                    });
                })
                ->paginate(10),

            'portalUsers' => $this->showAddModal ? $this->getPortalUsers() : [],
            'groups' => MdGroup::all(),
        ];
    }

    protected function getPortalUsers(): array
    {
        $cacheKey = 'portal_users_search_' . md5($this->addUserSearch);

        return Cache::remember($cacheKey, 300, function () {
            $searchTerm = str_replace(['%', '_'], ['\\%', '\\_'], $this->addUserSearch);

            return User::query()
                ->when($this->addUserSearch, function ($q) use ($searchTerm) {
                    $q->where(function ($inner) use ($searchTerm) {
                        $inner->where('first_name', 'ilike', "{$searchTerm}%")
                            ->orWhere('last_name', 'ilike', "{$searchTerm}%")
                            ->orWhere('nik', 'ilike', "{$searchTerm}%");
                    });
                })
                ->select('id', 'nik', 'first_name', 'last_name')
                ->orderBy('first_name')
                ->limit(8)
                ->get()
                ->map(fn ($u) => ['id' => $u->id, 'name' => "{$u->first_name} {$u->last_name} ({$u->nik})"])
                ->toArray();
        });
    }

    public function edit($id): void
    {
        $authorized = Authorized::with(['user', 'mdGroup'])->findOrFail($id);

        Gate::authorize('update', $authorized);

        $this->editingAuthorizedId = $id;
        $this->editUuid = $authorized->uuid;
        $this->editGroupId = $authorized->group_id;
        $this->editQuota = $authorized->quota;
        $this->editDisplayName = $authorized->user?->first_name . ' ' . $authorized->user?->last_name;
        $this->editDisplayNik = $authorized->user?->nik ?? '-';
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingAuthorizedId = null;
    }

    public function update(): void
    {
        $authorized = Authorized::findOrFail($this->editingAuthorizedId);

        Gate::authorize('update', $authorized);

        $this->validate((new UpdateAuthorizedRequest())->rules());

        try {
            $authorized->update([
                'group_id' => $this->editGroupId,
                'quota' => $this->editQuota,
            ]);

            $this->closeEditModal();
            $this->dispatch('notify', message: 'Authorized record updated successfully.', variant: 'success');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to update authorized record', [
                'error' => $e->getMessage(),
                'authorized_id' => $this->editingAuthorizedId,
            ]);
            $this->dispatch('notify', message: 'Failed to update authorized record. Please try again.', variant: 'danger');
        }
    }

    public function confirmDelete($id): void
    {
        $authorized = Authorized::with('user')->findOrFail($id);

        Gate::authorize('delete', $authorized);

        $this->deletingAuthorizedId = $id;
        $this->deleteName = trim(($authorized->user->first_name ?? '').' '.($authorized->user->last_name ?? ''));
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingAuthorizedId = null;
        $this->deleteName = '';
    }

    public function destroy(): void
    {
        try {
            $authorized = Authorized::findOrFail($this->deletingAuthorizedId);

            Gate::authorize('delete', $authorized);

            $authorized->delete();
            $this->closeDeleteModal();
            $this->dispatch('notify', message: 'Authorized record deleted successfully.', variant: 'success');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to delete authorized record', [
                'error' => $e->getMessage(),
                'authorized_id' => $this->deletingAuthorizedId,
            ]);
            $this->dispatch('notify', message: 'Failed to delete authorized record.', variant: 'danger');
        }
    }

    public function openAddModal(): void
    {
        Gate::authorize('create', Authorized::class);

        $this->reset(['addUuid', 'addUserId', 'addUserSearch', 'addGroupId', 'addQuota', 'addUuidSearch']);

        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->reset(['addUuidSearch', 'addUserSearch', 'addUserId']);
    }


    public function store(): void
    {
        Gate::authorize('create', Authorized::class);

        $this->validate((new StoreAuthorizedRequest())->rules());

        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                Authorized::create([
                    'uuid' => $this->addUuid,
                    'user_id' => $this->addUserId,
                    'group_id' => $this->addGroupId,
                    'quota' => $this->addQuota,
                ]);
            });

            $this->closeAddModal();
            $this->reset(['addUuid', 'addUserId', 'addUserSearch', 'addGroupId', 'addQuota']);
            $this->dispatch('notify', message: 'Authorized record created successfully.', variant: 'success');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create authorized record', [
                'error' => $e->getMessage(),
                'uuid' => $this->addUuid,
            ]);
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
        @can('create', App\Models\Authorized::class)
            <div>
                <flux:button wire:click="openAddModal" variant="primary" icon="plus">Add Authorized</flux:button>
            </div>
        @endcan
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" x-data="{ showFilters: false }">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:input
                wire:model.live.debounce.500ms="search"
                icon="magnifying-glass"
                placeholder="Search by name, NIK..."
                class="w-full sm:max-w-xs"
            />
            @php
                $activeFilterCount = collect([$filterGroup, $activeOnly])->filter(fn ($v) => $v !== '' && $v !== false)->count();
            @endphp
            <flux:button @click="showFilters = !showFilters" icon="funnel" :variant="$activeFilterCount > 0 ? 'primary' : 'filled'">
                Filter @if($activeFilterCount > 0) ({{ $activeFilterCount }}) @endif
            </flux:button>
        </div>

        <div x-show="showFilters" x-transition class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
            {{-- Group Filter --}}
            <flux:select wire:model.live="filterGroup" label="Group" placeholder="All Groups">
                <flux:select.option value="">All Groups</flux:select.option>
                @foreach($groups as $group)
                    <flux:select.option value="{{ $group->id }}">{{ ucfirst($group->nama_group) }}</flux:select.option>
                @endforeach
            </flux:select>

            {{-- Status Filter --}}
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Show active only</label>
                <div class="flex items-center h-10">
                    <flux:switch wire:model.live="activeOnly" />
                </div>
            </div>

            {{-- Reset Button --}}
            <div class="col-span-1 sm:col-span-2 flex justify-end gap-2 mt-2">
                <flux:button size="sm" wire:click="resetFilters" variant="ghost">Reset Filters</flux:button>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Full Name</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">NIK</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Group</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Quota</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        @if(auth()->user()->hasAnyPermission(['catera:authorized:update', 'catera:authorized:delete']))
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($authorizeds as $authorized)
                        <tr class="transition-colors duration-150 hover:bg-hover/20 dark:hover:bg-hover/30" wire:key="authorized-{{ $authorized->id }}">
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $authorized->user?->first_name }} {{ $authorized->user?->last_name }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $authorized->user?->nik ?? '-' }}</span>
                            </td>
                             <td class="px-4 py-3.5 text-center">
                                <flux:badge size="sm" :color="$authorized->mdGroup?->color ?? 'zinc'" inset="top bottom" class="w-20 justify-center">
                                    {{ ucfirst($authorized->mdGroup?->nama_group ?? '-') }}
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
                            @if(auth()->user()->can('update', $authorized) || auth()->user()->can('delete', $authorized))
                                <td class="px-4 py-3.5 text-center">
                                    <flux:dropdown>
                                        <flux:button icon="ellipsis-horizontal" size="sm" variant="ghost" />
                                        <flux:menu>
                                            @can('update', $authorized)
                                                <flux:menu.item wire:click="edit({{ $authorized->id }})" icon="pencil">Edit</flux:menu.item>
                                            @endcan

                                            @if(auth()->user()->can('update', $authorized) && auth()->user()->can('delete', $authorized))
                                                <flux:menu.separator />
                                            @endif

                                            @can('delete', $authorized)
                                                <flux:menu.item wire:click="confirmDelete({{ $authorized->id }})" icon="trash" variant="danger">Delete</flux:menu.item>
                                            @endcan
                                        </flux:menu>
                                    </flux:dropdown>
                                </td>
                            @endif
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

            {{-- User info (readonly, sourced from relationship) --}}
            <div class="grid grid-cols-2 gap-4">
                <flux:input
                    label="Full Name"
                    value="{{ $editDisplayName }}"
                    readonly
                    disabled
                    class="cursor-not-allowed opacity-70"
                />
                <flux:input
                    label="NIK"
                    value="{{ $editDisplayNik }}"
                    readonly
                    disabled
                    class="cursor-not-allowed opacity-70"
                />
            </div>

            <flux:radio.group wire:model="editGroupId" label="Group">
                <div class="grid grid-cols-3 gap-1.5">
                    @foreach($groups as $group)
                        @php
                            $color = $group->color ?? 'zinc';
                            $colorClasses = [
                                'zinc' => 'bg-zinc-500/30 border-zinc-600/30 dark:bg-zinc-600/30 dark:border-zinc-500/30',
                                'red' => 'bg-red-500/30 border-red-600/30 dark:bg-red-600/30 dark:border-red-500/30',
                                'blue' => 'bg-blue-500/30 border-blue-600/30 dark:bg-blue-600/30 dark:border-blue-500/30',
                                'green' => 'bg-green-500/30 border-green-600/30 dark:bg-green-600/30 dark:border-green-500/30',
                                'yellow' => 'bg-yellow-500/30 border-yellow-600/30 dark:bg-yellow-600/30 dark:border-yellow-500/30',
                                'orange' => 'bg-orange-500/30 border-orange-600/30 dark:bg-orange-600/30 dark:border-orange-500/30',
                                'purple' => 'bg-purple-500/30 border-purple-600/30 dark:bg-purple-600/30 dark:border-purple-500/30',
                                'pink' => 'bg-pink-500/30 border-pink-600/30 dark:bg-pink-600/30 dark:border-pink-500/30',
                                'indigo' => 'bg-indigo-500/30 border-indigo-600/ dark:bg-indigo-600/ dark:border-indigo-500/',
                            ];
                            $cardClass = $colorClasses[$color] ?? $colorClasses['zinc'];
                            $textClass = $color === 'yellow' ? 'text-zinc-900 dark:text-white' : 'text-black';
                            $descClass = $color === 'yellow' ? 'text-zinc-700 dark:text-zinc-100/90' : 'text-black/90';
                        @endphp
                        <label class="relative flex flex-col justify-start items-start rounded-xl border p-4 shadow-sm hover:opacity-90 transition-opacity cursor-pointer {{ $cardClass }}">
                            <div class="flex items-start gap-3 w-full">
                                <flux:radio value="{{ $group->id }}" class="mt-1 shrink-0" />
                                <div class="flex flex-col text-left">
                                    <span class="text-sm font-semibold {{ $textClass }}">{{ ucfirst($group->nama_group) }}</span>
                                    @if($group->short_description)
                                        <span class="mt-1 text-xs {{ $descClass }}">{{ $group->short_description }}</span>
                                    @endif
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </flux:radio.group>

            <flux:input wire:model="editQuota" label="Quota" type="number" />

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
                <flux:subheading>Authorize a new UUID and link it to a portal user.</flux:subheading>
            </div>

            <flux:input
                wire:model="addUuid"
                label="UUID"
                placeholder="Tap RFID card to auto-fill..."
                id="addUuid"
            />

            <x-ui.searchable-select
                label="Portal User"
                placeholder="Search by name or NIK..."
                wireModel="addUserId"
                searchWireModel="addUserSearch"
                :options="$portalUsers"
            />

            <flux:radio.group wire:model="addGroupId" label="Group">
                <div class="grid grid-cols-3 gap-1.5">
                    @foreach($groups as $group)
                        @php
                            $color = $group->color ?? 'zinc';
                            $colorClasses = [
                                'zinc' => 'bg-zinc-500/30 border-zinc-600/30 dark:bg-zinc-600/30 dark:border-zinc-500/30',
                                'red' => 'bg-red-500/30 border-red-600/30 dark:bg-red-600/30 dark:border-red-500/30',
                                'blue' => 'bg-blue-500/30 border-blue-600/30 dark:bg-blue-600/30 dark:border-blue-500/30',
                                'green' => 'bg-green-500/30 border-green-600/30 dark:bg-green-600/30 dark:border-green-500/30',
                                'yellow' => 'bg-yellow-500/30 border-yellow-600/30 dark:bg-yellow-600/30 dark:border-yellow-500/30',
                                'orange' => 'bg-orange-500/30 border-orange-600/30 dark:bg-orange-600/30 dark:border-orange-500/30',
                                'purple' => 'bg-purple-500/30 border-purple-600/30 dark:bg-purple-600/30 dark:border-purple-500/30',
                                'pink' => 'bg-pink-500/30 border-pink-600/30 dark:bg-pink-600/30 dark:border-pink-500/30',
                                'indigo' => 'bg-indigo-500/30 border-indigo-600/30 dark:bg-indigo-600/30 dark:border-indigo-500/30',
                            ];
                            $cardClass = $colorClasses[$color] ?? $colorClasses['zinc'];
                            $textClass = $color === 'yellow' ? 'text-zinc-900 dark:text-white' : 'text-black';
                            $descClass = $color === 'yellow' ? 'text-zinc-700 dark:text-zinc-100/90' : 'text-black/90';
                        @endphp
                        <label class="relative flex flex-col justify-start items-start rounded-xl border p-4 shadow-sm hover:opacity-90 transition-opacity cursor-pointer {{ $cardClass }}">
                            <div class="flex items-start gap-3 w-full">
                                <flux:radio value="{{ $group->id }}" class="mt-1 shrink-0" />
                                <div class="flex flex-col text-left">
                                    <span class="text-sm font-semibold {{ $textClass }}">{{ ucfirst($group->nama_group) }}</span>
                                    @if($group->short_description)
                                        <span class="mt-1 text-xs {{ $descClass }}">{{ $group->short_description }}</span>
                                    @endif
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </flux:radio.group>

            <flux:input wire:model="addQuota" label="Quota" type="number" />

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
