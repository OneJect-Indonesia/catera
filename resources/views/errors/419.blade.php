@extends('errors.layout')

@section('title', '419 - Page Expired')

@section('content')
    <div class="text-center mb-8">
        <div class="text-7xl font-bold text-[#4da8cf] mb-3">419</div>
        <h1 class="text-4xl font-semibold text-[#1b1b18] mb-2">Session Expired</h1>
        <p class="text-[#4da8cf]/70">Page Expired</p>
    </div>

    <div class="mb-8">
        <p class="text-base text-[#1b1b18] leading-relaxed text-center">
            The page has expired due to inactivity. Please refresh the page and try your request again.
        </p>
    </div>

    <div class="flex gap-4 justify-between">
        <button onclick="location.reload()" class="flex-1 px-6 py-3 bg-[#4da8cf] hover:bg-[#3f8f81] text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
            Refresh Page
        </button>
        <a href="{{ config('services.sso.portal_url') }}" class="flex-1 px-6 py-3 bg-white/30 hover:bg-white/40 backdrop-blur-sm border border-white/50 text-center text-[#1b1b18] font-semibold rounded-xl transition-all">
            Back to Portal
        </a>
    </div>
@endsection
