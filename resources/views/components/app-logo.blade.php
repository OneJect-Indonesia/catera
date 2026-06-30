@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand {{ $attributes }}>
    </flux:sidebar.brand>
@else
    <flux:brand {{ $attributes }}>
    </flux:brand>
@endif