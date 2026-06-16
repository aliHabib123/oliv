<div>
    <h1 class="text-2xl font-bold" style="color: var(--oliv-primary);">Connect your Shopify store</h1>
    <p class="mt-1 text-gray-500">Enter your store URL and we'll import your products, branding, and settings instantly.</p>

    <div class="mt-8 mx-auto max-w-md rounded-xl border border-gray-200 bg-gray-50 p-8 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-lg border border-gray-200 bg-white">
            <svg class="h-6 w-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M15.34 3.41c-.01-.01-.03-.02-.05-.02-.02 0-.04.01-.05.02l-.95.39-.56-1.36c-.04-.09-.14-.14-.24-.14h-.01c-.1 0-.2.05-.24.14l-.82 1.34-1.47-.37c-.1-.02-.2.01-.27.08-.06.07-.08.17-.05.26l.4 1.36-1.36.57c-.09.04-.15.13-.15.23 0 .1.06.19.15.23l1.2.76-.76 1.2c-.04.07-.04.15 0 .22.04.07.11.11.19.12l1.42.18-.18 1.42c-.01.1.04.19.13.24.04.02.08.03.12.03.06 0 .11-.02.15-.06l1.06-.91.91 1.06c.06.07.15.1.24.09.09-.02.16-.08.19-.16l.57-1.36 1.36.57c.03.01.06.02.09.02.07 0 .14-.03.18-.09.06-.07.07-.17.04-.26l-.4-1.36 1.36-.57c.09-.04.15-.13.15-.23 0-.1-.06-.19-.15-.23l-1.2-.76.76-1.2c.06-.1.04-.22-.04-.3z"/>
            </svg>
        </div>

        <h3 class="mt-4 text-lg font-semibold" style="color: var(--oliv-primary);">Connect to Shopify</h3>
        <p class="mt-1 text-sm text-gray-500">We'll import your store name, products, collections, and branding automatically.</p>

        <div class="mt-6">
            <input
                type="text"
                wire:model="shopifyDomain"
                placeholder="your-store.myshopify.com or yourdomain.com"
                class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:border-transparent"
            />
            <p class="mt-1.5 text-xs text-gray-400">Enter your .myshopify.com domain or custom domain</p>

            <button
                wire:click="connectPublic"
                wire:loading.attr="disabled"
                class="mt-4 w-full rounded-lg px-6 py-2.5 text-sm font-semibold text-white transition-colors disabled:opacity-60"
                style="background-color: #4a7c59;"
                onmouseover="this.style.backgroundColor='#3d6b4a'"
                onmouseout="this.style.backgroundColor='#4a7c59'"
            >
                <span wire:loading.remove wire:target="connectPublic">🔗 Connect & Import</span>
                <span wire:loading wire:target="connectPublic" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Importing store data...
                </span>
            </button>
        </div>

        @error('shopifyDomain')
            <div class="mt-3 rounded-lg bg-red-50 border border-red-200 p-2 text-xs text-red-700">
                {{ $message }}
            </div>
        @enderror
    </div>

    {{-- Divider --}}
    <div class="mt-8 flex items-center gap-4 mx-auto max-w-md">
        <div class="flex-1 border-t border-dashed border-gray-300"></div>
        <span class="text-xs text-gray-400">— or —</span>
        <div class="flex-1 border-t border-dashed border-gray-300"></div>
    </div>

    {{-- Manual fallback --}}
    <div class="mt-4 text-center">
        <button
            wire:click="selectPlatform('manual')"
            class="rounded-lg border border-dashed border-gray-300 px-6 py-2 text-sm text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors"
        >
            Set up manually instead
        </button>
    </div>

    @if (session('error'))
        <div class="mt-6 mx-auto max-w-md rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif
</div>
