<nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
    <div class="flex justify-between flex-1 sm:hidden">
        @if ($paginator->onFirstPage())
        <span
            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-zinc-400 bg-zinc-100 border border-zinc-300 cursor-default rounded-md dark:bg-zinc-800 dark:border-zinc-700">
            Previous </span>
        @else
        <a href="{{ $paginator->previousPageUrl() }}"
            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-md hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600">
            Previous </a>
        @endif

        @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}"
            class="relative inline-flex items-center ml-3 px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-md hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600">
            Next </a>
        @else
        <span
            class="relative inline-flex items-center ml-3 px-4 py-2 text-sm font-medium text-zinc-400 bg-zinc-100 border border-zinc-300 cursor-default rounded-md dark:bg-zinc-800 dark:border-zinc-700">
            Next </span>
        @endif
    </div>

    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-zinc-700 dark:text-zinc-400">
                Showing <span class="font-semibold">{{ $paginator->firstItem() }}</span> to <span
                    class="font-semibold">{{ $paginator->lastItem() }}</span> of <span class="font-semibold">{{
                    $paginator->total() }}</span> results
            </p>
        </div>

        <div>
            <span class="relative z-0 inline-flex shadow-sm rounded-md">
                {{-- Tombol Previous --}}
                @if ($paginator->onFirstPage())
                <span
                    class="px-3 py-2 rounded-l-md border border-zinc-300 bg-zinc-50 text-zinc-400 dark:bg-zinc-800 dark:border-zinc-700">Prev</span>
                @else
                <button wire:click="previousPage"
                    class="px-3 py-2 rounded-l-md border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600">Prev</button>
                @endif

                {{-- Nomor Halaman --}}
                @foreach ($elements as $element)
                @if (is_string($element))
                <span
                    class="px-4 py-2 border border-zinc-300 bg-zinc-50 text-zinc-400 dark:bg-zinc-800 dark:border-zinc-700">{{
                    $element }}</span>
                @endif

                @if (is_array($element))
                @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                <span
                    class="px-4 py-2 border border-emerald-500 bg-emerald-50 text-emerald-600 font-bold z-10 dark:bg-emerald-900/20">{{
                    $page }}</span>
                @else
                <button wire:click="gotoPage({{ $page }})"
                    class="px-4 py-2 border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600">{{
                    $page }}</button>
                @endif
                @endforeach
                @endif
                @endforeach

                {{-- Tombol Next --}}
                @if ($paginator->hasMorePages())
                <button wire:click="nextPage"
                    class="px-3 py-2 rounded-r-md border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600">Next</button>
                @endif
            </span>
        </div>
    </div>
</nav>
