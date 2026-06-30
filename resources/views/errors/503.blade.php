@extends('errors.layout')

@section('title', '503 - Service Unavailable')

@section('content')
    <div class="text-center mb-8">
        <div class="text-7xl font-bold text-[#4da8cf] mb-3">503</div>
        <h1 class="text-4xl font-semibold text-[#1b1b18] mb-2">Service Unavailable</h1>
        <p class="text-[#4da8cf]/70">Under Maintenance</p>
    </div>

    <div class="mb-8">
        <p class="text-base text-[#1b1b18] leading-relaxed text-center">
            The service is temporarily unavailable. We are performing scheduled maintenance or updates and will be back shortly.
        </p>
    </div>

    <div class="flex gap-4 justify-between">
        <button onclick="location.reload()" class="flex-1 px-6 py-3 bg-[#4da8cf] hover:bg-[#3f8f81] text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
            Refresh Page
        </button>
    </div>
@endsection
