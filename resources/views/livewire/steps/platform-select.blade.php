<div>
    <h1 class="text-2xl font-bold" style="color: var(--oliv-primary);">Choose your platform</h1>
    <p class="mt-1 text-gray-500">Select where your store lives. We'll tailor the setup for you.</p>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
        {{-- Shopify --}}
        <button
            wire:click="selectPlatform('shopify')"
            class="group relative flex items-center gap-4 rounded-xl border-2 p-5 text-left transition-all hover:shadow-md"
            style="border-color: var(--oliv-green); background-color: var(--oliv-green-bg);"
        >
            <div class="flex h-11 w-11 items-center justify-center rounded-lg text-white font-bold text-lg" style="background-color: var(--oliv-green);">
                S
            </div>
            <div>
                <span class="text-base font-semibold" style="color: var(--oliv-primary);">Shopify</span>
                <p class="text-sm text-gray-500">Auto-import ready</p>
            </div>
            <span class="absolute top-3 right-3 rounded px-1.5 py-0.5 text-[10px] font-bold text-white" style="background-color: var(--oliv-green);">
                MVP
            </span>
        </button>

        {{-- Manual Entry --}}
        <button
            wire:click="selectPlatform('manual')"
            class="group flex items-center gap-4 rounded-xl border-2 border-gray-200 p-5 text-left transition-all hover:border-gray-300 hover:shadow-md"
        >
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-gray-100 text-gray-500 font-bold text-lg">
                +
            </div>
            <div>
                <span class="text-base font-semibold" style="color: var(--oliv-primary);">Manual Setup</span>
                <p class="text-sm text-gray-500">Enter details yourself</p>
            </div>
        </button>
    </div>
</div>
