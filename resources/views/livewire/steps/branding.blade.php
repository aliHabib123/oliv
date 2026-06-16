<div>
    <h1 class="text-2xl font-bold" style="color: var(--oliv-primary);">App branding</h1>
    <p class="mt-1 text-gray-500">Your store logo and brand colors are already applied. Fine-tune anything below.</p>

    <div class="mt-8 space-y-6">
        {{-- App Name --}}
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">App Name</label>
                @if ($platform === 'shopify' && !empty($branding['app_name']))
                    <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-medium" style="background-color: var(--oliv-green-bg); color: #276749;">✓ auto-filled</span>
                @endif
            </div>
            <input
                type="text"
                wire:model.live="branding.app_name"
                class="block w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:border-transparent"
                placeholder="My Store App"
            />
        </div>

        {{-- App Icon --}}
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">App Icon</label>
                @if ($platform === 'shopify' && !empty($branding['app_icon_url']))
                    <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-medium" style="background-color: var(--oliv-green-bg); color: #276749;">✓ imported from store logo</span>
                @endif
            </div>
            <div class="flex items-center gap-4">
                @if (!empty($branding['app_icon_url']))
                    <div class="h-20 w-20 flex-shrink-0 rounded-2xl overflow-hidden border border-gray-200 shadow-sm bg-white">
                        <img src="{{ $branding['app_icon_url'] }}" alt="App Icon" class="h-full w-full object-contain">
                    </div>
                @else
                    <div class="h-20 w-20 flex-shrink-0 rounded-2xl flex items-center justify-center text-2xl font-bold text-white" style="background-color: var(--oliv-accent);">
                        {{ strtoupper(substr($branding['app_name'] ?? 'A', 0, 1)) }}
                    </div>
                @endif
                <div class="flex-1">
                    <input
                        type="url"
                        wire:model.live="branding.app_icon_url"
                        class="block w-full rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:border-transparent"
                        placeholder="https://..."
                    />
                    <p class="mt-1 text-xs text-gray-400">Recommended: 1024×1024px square PNG</p>
                </div>
            </div>
        </div>

        {{-- Colors --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Primary Color</label>
                    @if ($platform === 'shopify' && !empty($branding['primary_color']) && $branding['primary_color'] !== '#000000')
                        <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-medium" style="background-color: var(--oliv-green-bg); color: #276749;">✓ from theme</span>
                    @endif
                </div>
                <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2">
                    <input type="color" wire:model.live="branding.primary_color" class="h-7 w-7 cursor-pointer rounded border-0 p-0" />
                    <input type="text" wire:model.live="branding.primary_color" class="flex-1 border-0 text-sm focus:outline-none focus:ring-0 p-0" placeholder="#000000" />
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Secondary Color</label>
                </div>
                <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2">
                    <input type="color" wire:model.live="branding.secondary_color" class="h-7 w-7 cursor-pointer rounded border-0 p-0" />
                    <input type="text" wire:model.live="branding.secondary_color" class="flex-1 border-0 text-sm focus:outline-none focus:ring-0 p-0" placeholder="#ffffff" />
                </div>
            </div>
        </div>

        {{-- Splash Screen --}}
        <div>
            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1 block">Splash Screen</label>
            <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center">
                <input
                    type="url"
                    wire:model.live="branding.splash_url"
                    class="block w-full rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:border-transparent mb-2"
                    placeholder="https://..."
                />
                <p class="text-xs text-gray-400">Upload custom splash or use app icon</p>
            </div>
        </div>
    </div>
</div>
