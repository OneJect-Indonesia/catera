@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand {{ $attributes }}>
        <x-slot name="logo" class="flex items-center justify-center">
            <img src="{{ asset('Oneject_logo.webp') }}" alt="Oneject Logo" class="max-w-sm object-contain" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand {{ $attributes }}>
        <x-slot name="logo" class="flex items-center justify-center">
            <img src="{{ asset('Oneject_logo.webp') }}" alt="Oneject Logo" class="max-w-sm object-contain" />
        </x-slot>
    </flux:brand>
@endif
