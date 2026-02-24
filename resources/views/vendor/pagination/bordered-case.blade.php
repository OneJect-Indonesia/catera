@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
    {{-- Tampilan Mobile --}}
    <div class="flex justify-between flex-1 sm:hidden">
        @if ($paginator->onFirstPage())
        <span
            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-zinc-400 bg-zinc-50 border border-zinc-300 rounded-md cursor-default dark:bg-zinc-800 dark:border-zinc-700">
            Previous
        </span>
        @else
        <button wire:click="previousPage"
            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-md hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600">
            Previous
        </button>
        @endif

        @if ($paginator->hasMorePages())
        <button wire:click="nextPage"
            class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-md hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600">
            Next
        </button>
        @else
        <span
            class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-zinc-400 bg-zinc-50 border border-zinc-300 rounded-md cursor-default dark:bg-zinc-800 dark:border-zinc-700">
            Next
        </span>
        @endif
    </div>

    {{-- Tampilan Desktop --}}
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Menampilkan <span class="font-bold">{{ $paginator->firstItem() }}</span> - <span class="font-bold">{{
                    $paginator->lastItem() }}</span> dari <span class="font-bold">{{ $paginator->total() }}</span> data
            </p>
        </div>

        <div>
            <span class="relative z-0 inline-flex -space-x-px shadow-sm rounded-md">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                <span
                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-zinc-300 bg-zinc-50 text-zinc-400 cursor-default dark:bg-zinc-800 dark:border-zinc-700">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                </span>
                @else
                <button wire:click="previousPage"
                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-zinc-300 bg-white text-zinc-500 hover:bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-400 dark:hover:bg-zinc-700 transition">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                @endif

                {{-- Pagination Elements --}}
                @php
                    $currentPage = $paginator->currentPage();
                    $lastPage = $paginator->lastPage();

                    // Calculate the start and end pages to show only 6 total page buttons.
                    $start = max(1, $currentPage - 2);
                    $end = min($lastPage, $start + 4);

                    // Adjust if we hit the end
                    if ($end - $start < 4) {
                        $start = max(1, $end - 4);
                    }

                    // Convert into a simple array of pages to render
                    $pages = range($start, $end);
                @endphp

                {{-- Show first page if we are far from the start --}}
                @if($start > 1)
                    <button wire:click="gotoPage(1)"
                        class="relative inline-flex items-center px-4 py-2 border border-zinc-300 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700 transition">
                        1
                    </button>
                    @if($start > 2)
                        <span class="relative inline-flex items-center px-4 py-2 border border-zinc-300 bg-zinc-50 text-zinc-700 dark:bg-zinc-800 dark:border-zinc-700">...</span>
                    @endif
                @endif

                @foreach ($pages as $page)
                    @if ($page == $currentPage)
                    <span
                        class="relative inline-flex items-center px-4 py-2 border border-primary bg-primary/10 text-sm font-bold text-primary z-10 dark:bg-primary/20">
                        {{ $page }}
                    </span>
                    @else
                    <button wire:click="gotoPage({{ $page }})"
                        class="relative inline-flex items-center px-4 py-2 border border-zinc-300 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700 transition">
                        {{ $page }}
                    </button>
                    @endif
                @endforeach

                {{-- Show last page if there are more than 6 --}}
                @if($end < $lastPage)
                    @if($end < $lastPage - 1)
                        <span class="relative inline-flex items-center px-4 py-2 border border-zinc-300 bg-zinc-50 text-zinc-700 dark:bg-zinc-800 dark:border-zinc-700">...</span>
                    @endif
                    <button wire:click="gotoPage({{ $lastPage }})"
                        class="relative inline-flex items-center px-4 py-2 border border-zinc-300 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700 transition">
                        {{ $lastPage }}
                    </button>
                @endif

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                <button wire:click="nextPage"
                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-zinc-300 bg-white text-zinc-500 hover:bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-400 dark:hover:bg-zinc-700 transition">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                @else
                <span
                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-zinc-300 bg-zinc-50 text-zinc-400 cursor-default dark:bg-zinc-800 dark:border-zinc-700">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                </span>
                @endif
            </span>
        </div>
    </div>
</nav>
@endif
