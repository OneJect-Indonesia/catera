@extends('errors.layout')

@section('title', '429 - Too Many Requests')

@section('content')
    <div class="text-center mb-8">
        <div class="text-7xl font-bold text-[#4da8cf] mb-3">429</div>
        <h1 class="text-4xl font-semibold text-[#1b1b18] mb-2">Too Many Requests</h1>
        <p class="text-[#4da8cf]/70">Rate Limit Exceeded</p>
    </div>

    <div class="mb-8">
        <p class="text-base text-[#1b1b18] leading-relaxed text-center">
            You have sent too many requests in a given amount of time. Please slow down and try again shortly.
        </p>
    </div>

    <div class="flex gap-4 justify-between">
        <button onclick="location.reload()" class="flex-1 px-6 py-3 bg-[#4da8cf] hover:bg-[#3f8f81] text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
            Try Again
        </button>
        <a href="{{ config('services.sso.portal_url') }}" class="flex-1 px-6 py-3 bg-white/30 hover:bg-white/40 backdrop-blur-sm border border-white/50 text-center text-[#1b1b18] font-semibold rounded-xl transition-all">
            Back to Portal
        </a>
    </div>
@endsection
