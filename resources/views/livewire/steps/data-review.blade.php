<div>
    <h1 class="text-2xl font-bold" style="color: var(--oliv-primary);">Review imported data</h1>
    <p class="mt-1 text-gray-500">All fields pre-filled from your Shopify store. Edit anything you need to change.</p>

    @if (! $session->isImportReady() && $platform === 'shopify')
        <div class="mt-8 flex flex-col items-center justify-center py-16" wire:poll.2s>
            <svg class="h-8 w-8 animate-spin" style="color: var(--oliv-green);" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="mt-4 text-sm text-gray-600">Importing your store data...</p>
            <p class="mt-1 text-xs text-gray-400">This usually takes a few seconds.</p>
        </div>
    @else
        {{-- Connection banner --}}
        @if ($platform === 'shopify')
            <div class="mt-6 flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm" style="border-color: var(--oliv-green); background-color: var(--oliv-green-bg); color: #276749;">
                <svg class="h-4 w-4" style="color: var(--oliv-green);" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                <span>Connected: <strong>{{ $storeData['name'] ?? 'Store' }}</strong> ({{ $shopifyDomain }}) — {{ $storeData['product_count'] ?? 0 }} products, {{ count($session->imported_data['catalog']['categories'] ?? []) }} collections imported</span>
            </div>
        @endif

        {{-- Form fields --}}
        <div class="mt-6 space-y-5">
            {{-- Store Name --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Store Name</label>
                    @if ($platform === 'shopify')
                        <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-medium" style="background-color: var(--oliv-green-bg); color: #276749;">✓ auto-filled</span>
                    @endif
                </div>
                <input
                    type="text"
                    wire:model="storeData.name"
                    class="block w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:border-transparent"
                />
            </div>

            {{-- Store URL --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Store URL</label>
                    @if ($platform === 'shopify')
                        <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-medium" style="background-color: var(--oliv-green-bg); color: #276749;">✓ auto-filled</span>
                    @endif
                </div>
                <input
                    type="text"
                    wire:model="shopifyDomain"
                    class="block w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:border-transparent"
                />
            </div>

            {{-- Currency & Industry --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Currency</label>
                        @if ($platform === 'shopify')
                            <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-medium" style="background-color: var(--oliv-green-bg); color: #276749;">✓ auto-filled</span>
                        @endif
                    </div>
                    <input
                        type="text"
                        wire:model="storeData.currency"
                        class="block w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:border-transparent"
                        placeholder="AED"
                    />
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1 block">Industry</label>
                    <input
                        type="text"
                        wire:model="storeData.industry"
                        class="block w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:border-transparent"
                        placeholder="e.g. Fashion"
                    />
                </div>
            </div>

            {{-- Owner Email --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Owner Email</label>
                    @if ($platform === 'shopify')
                        <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-medium" style="background-color: var(--oliv-green-bg); color: #276749;">✓ auto-filled</span>
                    @endif
                </div>
                <input
                    type="email"
                    wire:model="storeData.email"
                    class="block w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:border-transparent"
                />
            </div>

            {{-- Categories --}}
            @php
                $categories = $session->imported_data['catalog']['categories'] ?? [];
            @endphp
            @if (count($categories) > 0)
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Categories ({{ count($categories) }})</label>
                        @if ($platform === 'shopify')
                            <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-medium" style="background-color: var(--oliv-green-bg); color: #276749;">✓ auto-filled</span>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        @foreach ($categories as $category)
                            <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2">
                                @if (!empty($category['image']))
                                    <img src="{{ $category['image'] }}" alt="" class="h-6 w-6 rounded object-cover flex-shrink-0">
                                @else
                                    <div class="h-6 w-6 rounded bg-gray-100 flex-shrink-0 flex items-center justify-center">
                                        <svg class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </div>
                                @endif
                                <span class="text-xs text-gray-700 truncate">{{ $category['title'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
