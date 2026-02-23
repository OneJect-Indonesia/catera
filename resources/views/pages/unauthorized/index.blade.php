<?php

use App\Models\Unauthorized;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public bool $showDeleteModal = false;
    public $deletingUnauthorizedId = null;
    public string $deleteUuid = '';

    public function with(): array
    {
        return [
            'unauthorizeds' => Unauthorized::query()
                ->when($this->search, function ($query) {
                    $query->where('uuid', 'like', '%' . $this->search . '%');
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10),
        ];
    }

    public function confirmDelete($id)
    {
        $unauthorized = Unauthorized::findOrFail($id);
        $this->deletingUnauthorizedId = $id;
        $this->deleteUuid = $unauthorized->uuid;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingUnauthorizedId = null;
        $this->deleteUuid = '';
    }

    public function destroy()
    {
        try {
            Unauthorized::findOrFail($this->deletingUnauthorizedId)->delete();
            $this->closeDeleteModal();
            $this->dispatch('notify', message: 'Unauthorized record deleted successfully.', variant: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Failed to delete unauthorized record.', variant: 'danger');
        }
    }
}; ?>

<x-slot name="title">Unauthorized</x-slot>

<div class="flex h-full w-full flex-1 flex-col gap-6">

    {{-- Page Header --}}
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="xl" level="1">Unauthorized List</flux:heading>
            <flux:subheading size="lg">Manage unauthorized UUIDs caught by the system.</flux:subheading>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:flex-row sm:items-center sm:justify-between">
        <flux:input
            wire:model.live="search"
            icon="magnifying-glass"
            placeholder="Search by UUID..."
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
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Detected At</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($unauthorizeds as $unauthorized)
                        <tr class="transition-colors duration-150 hover:bg-hover/20 dark:hover:bg-hover/30">
                            <td class="px-4 py-3.5 text-center">
                                <span class="font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $unauthorized->uuid }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $unauthorized->created_at->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <flux:button wire:click="confirmDelete({{ $unauthorized->id }})" size="sm" variant="danger" icon="trash">Delete</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-center text-sm text-zinc-400 dark:text-zinc-500">
                                No unauthorized records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($unauthorizeds->hasPages())
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
                {{ $unauthorizeds->links('vendor.pagination.bordered-case') }}
            </div>
        @endif
    </div>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="delete-unauthorized" wire:model.live="showDeleteModal" class="md:w-md">
        <div class="space-y-5">
            <div class="flex items-start gap-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon name="exclamation-triangle" class="size-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:heading size="lg">Delete Unauthorized Record</flux:heading>
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
