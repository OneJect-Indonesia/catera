@props(['on'])

<div
    x-data="{ show: false, message: '', variant: 'success' }"
    x-on:{{ $on }}.window="
        let payload = $event.detail[0] ? $event.detail[0] : $event.detail;
        message = payload.message;
        variant = payload.variant || 'success';
        show = true;
        setTimeout(() => show = false, 3000);
    "
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
    x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
    x-transition:leave-end="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
    class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6 z-9999"
    style="display: none;"
>
    <!-- Notification panel, dynamically center-aligned vertically on smaller screens, and aligned to the top-right on medium and larger screens -->
    <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
        <div
            class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-zinc-800 dark:ring-white/10"
            :class="{
                'border-l-4 border-emerald-500': variant === 'success',
                'border-l-4 border-red-500': variant === 'danger'
            }"
        >
            <div class="p-4">
                <div class="flex items-start">
                    <div class="shrink-0">
                        <!-- Success Icon -->
                        <svg x-show="variant === 'success'" class="h-6 w-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <!-- Error Icon -->
                        <svg x-show="variant === 'danger'" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100" x-text="variant === 'success' ? 'Success!' : 'Error!'"></p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400" x-text="message"></p>
                    </div>
                    <div class="ml-4 flex shrink-0">
                        <button @click="show = false" type="button" class="inline-flex rounded-md bg-white text-zinc-400 hover:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 dark:bg-zinc-800 dark:hover:text-zinc-300">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
