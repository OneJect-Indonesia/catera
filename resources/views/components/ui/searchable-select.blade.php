@props([
    'options' => [],
    'wireModel' => '',
    'label' => 'Select Option',
    'placeholder' => 'Search...',
    'valueKey' => 'id',
    'labelKey' => 'name'
])

<div
    class="relative w-full"
    x-data="{
        open: false,
        search: '',
        selectedIndex: 0,
        options: @js($options),
        wireModelValue: @entangle($wireModel),

        get filteredOptions() {
            if (this.search === '') {
                return this.options;
            }
            return this.options.filter(option =>
                String(option['{{ $labelKey }}']).toLowerCase().includes(this.search.toLowerCase()) ||
                String(option['{{ $valueKey }}']).toLowerCase().includes(this.search.toLowerCase())
            );
        },

        get selectedLabel() {
            const selected = this.options.find(opt => opt['{{ $valueKey }}'] == this.wireModelValue);
            return selected ? selected['{{ $labelKey }}'] : '{{ $placeholder }}';
        },

        selectOption(option) {
            if (option) {
                this.wireModelValue = option['{{ $valueKey }}'];
                this.search = '';
                this.open = false;
            }
        },

        highlightNext() {
            if (this.open) {
                if (this.selectedIndex < this.filteredOptions.length - 1) {
                    this.selectedIndex++;
                    this.scrollToHighlighted();
                }
            } else {
                this.open = true;
            }
        },

        highlightPrevious() {
            if (this.open) {
                if (this.selectedIndex > 0) {
                    this.selectedIndex--;
                    this.scrollToHighlighted();
                }
            } else {
                this.open = true;
            }
        },

        selectHighlighted() {
            if (this.open && this.filteredOptions.length > 0) {
                this.selectOption(this.filteredOptions[this.selectedIndex]);
            } else {
                this.open = true;
            }
        },

        scrollToHighlighted() {
            this.$nextTick(() => {
                const listbox = this.$refs.listbox;
                const activeItem = listbox.children[this.selectedIndex];
                if (activeItem) {
                    const listboxRect = listbox.getBoundingClientRect();
                    const itemRect = activeItem.getBoundingClientRect();

                    if (itemRect.bottom > listboxRect.bottom) {
                        listbox.scrollTop += itemRect.bottom - listboxRect.bottom;
                    } else if (itemRect.top < listboxRect.top) {
                        listbox.scrollTop -= listboxRect.top - itemRect.top;
                    }
                }
            });
        },

        openDropdown() {
            this.open = true;
            this.search = '';
            this.selectedIndex = 0;
            this.$nextTick(() => {
                this.$refs.searchInput.focus();
            });
        }
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    <flux:field>
        <flux:label>{{ $label }}</flux:label>

        <div class="relative">
            {{-- Display Button --}}
            <button
                type="button"
                @click="openDropdown"
                @keydown.arrow-up.prevent="highlightPrevious"
                @keydown.arrow-down.prevent="highlightNext"
                @keydown.enter.prevent="openDropdown"
                class="w-full flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:focus:border-primary-400 dark:focus:ring-primary-400"
                :class="{ 'border-primary-500 ring-1 ring-primary-500 dark:border-primary-400 dark:ring-primary-400': open }"
            >
                <span x-text="selectedLabel" class="block truncate" :class="{ 'text-zinc-500 dark:text-zinc-400': !wireModelValue }"></span>
                <flux:icon name="chevron-up-down" class="size-4 text-zinc-400" />
            </button>

            {{-- Dropdown Panel --}}
            <div
                x-show="open"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute z-50 mt-1 w-full rounded-md bg-white shadow-lg dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700"
                style="display: none;"
            >
                {{-- Search Input --}}
                <div class="p-2 border-b border-zinc-100 dark:border-zinc-700">
                    <div class="relative flex items-center">
                        <flux:icon name="magnifying-glass" class="absolute left-3 size-4 text-zinc-400" />
                        <input
                            type="text"
                            x-ref="searchInput"
                            x-model="search"
                            @keydown.arrow-up.prevent="highlightPrevious"
                            @keydown.arrow-down.prevent="highlightNext"
                            @keydown.enter.prevent="selectHighlighted"
                            class="w-full rounded-md border-0 bg-zinc-50 py-1.5 pl-9 pr-3 text-sm text-zinc-900 focus:ring-0 dark:bg-zinc-900/50 dark:text-white placeholder-zinc-400"
                            placeholder="Search..."
                            autocomplete="off"
                        >
                    </div>
                </div>

                {{-- Options List --}}
                <ul
                    x-ref="listbox"
                    class="max-h-60 overflow-auto py-1 text-sm text-zinc-800 dark:text-zinc-200"
                    tabindex="-1"
                    role="listbox"
                >
                    <template x-for="(option, index) in filteredOptions" :key="option['{{ $valueKey }}']">
                        <li
                            @click="selectOption(option)"
                            @mouseenter="selectedIndex = index"
                            class="relative cursor-default select-none py-2 pl-3 pr-9"
                            :class="{
                                'bg-primary-50 text-primary-900 dark:bg-primary-500/10 dark:text-primary-400': selectedIndex === index,
                                'text-zinc-900 dark:text-zinc-200': selectedIndex !== index
                            }"
                            role="option"
                        >
                            <span x-text="option['{{ $labelKey }}']" class="block truncate" :class="{ 'font-semibold': wireModelValue == option['{{ $valueKey }}'], 'font-normal': wireModelValue != option['{{ $valueKey }}'] }"></span>

                            <span
                                x-show="wireModelValue == option['{{ $valueKey }}']"
                                class="absolute inset-y-0 right-0 flex items-center pr-4"
                                :class="{ 'text-primary-600 dark:text-primary-400': selectedIndex === index, 'text-primary-600 dark:text-primary-500': selectedIndex !== index }"
                            >
                                <flux:icon name="check" class="size-4" />
                            </span>
                        </li>
                    </template>

                    {{-- No Results --}}
                    <li x-show="filteredOptions.length === 0" class="relative cursor-default select-none py-2 px-3 text-zinc-500 dark:text-zinc-400">
                        No results found
                    </li>
                </ul>
            </div>
        </div>
    </flux:field>
</div>
