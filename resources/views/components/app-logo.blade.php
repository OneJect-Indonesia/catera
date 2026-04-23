@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Catera Dashboard" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <img src="{{ asset('Oneject_Logo.webp') }}" alt="Catera Admin Dashboard" class="w-full h-full" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Catera Dashboard" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <img src="{{ asset('Oneject_Logo.webp') }}" alt="Catera Admin Dashboard" class="w-full h-full" />
        </x-slot>
    </flux:brand>
@endif