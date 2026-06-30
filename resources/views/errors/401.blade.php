@extends('errors.layout')

@section('title', '401 - Unauthorized')

@section('content')
    <div class="text-center mb-8">
        <div class="text-7xl font-bold text-[#4da8cf] mb-3">401</div>
        <h1 class="text-4xl font-semibold text-[#1b1b18] mb-2">Unauthorized</h1>
        <p class="text-[#4da8cf]/70">Authentication Required</p>
    </div>

    <div class="mb-8">
        <p class="text-base text-[#1b1b18] leading-relaxed text-center">
            You need to log in to access this page. Please authenticate with your credentials to continue.
        </p>
    </div>

    <div class="flex gap-4 justify-between text-center">
        <a href="{{ config('services.sso.portal_url') }}" class="flex-1 px-6 py-3 bg-[#4da8cf] hover:bg-[#3f8f81] text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
            Go to Login
        </a>
        <a href="{{ config('services.sso.portal_url') }}" class="flex-1 px-6 py-3 bg-white/30 hover:bg-white/40 backdrop-blur-sm border border-white/50 text-[#1b1b18] font-semibold rounded-xl transition-all">
            Back to Portal
        </a>
    </div>
@endsection
