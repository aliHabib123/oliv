<x-filament-panels::page>
    <div>
        @if($addons->isEmpty())
            <x-filament::section>
                <div class="text-center py-8">
                    <p class="text-gray-500">No add-ons available at the moment.</p>
                </div>
            </x-filament::section>
        @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            @foreach($addons as $addon)
                @php
                    $isSubscribed = in_array($addon->id, $subscribedAddons);
                    $tenantAddon = null;
                    if ($isSubscribed) {
                        $tenantAddon = \App\Models\TenantAddon::where('tenant_id', Auth::user()->tenant->id)
                            ->where('addon_id', $addon->id)
                            ->where('status', 'active')
                            ->first();
                    }
                @endphp

                <x-filament::section>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1.5rem;">
                        <div style="flex: 1; min-width: 0;">
                            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">{{ $addon->name }}</h3>

                            @if($isSubscribed)
                                <div style="margin-bottom: 0.75rem;">
                                    <x-filament::badge color="success" icon="heroicon-m-check-circle">
                                        Active
                                    </x-filament::badge>
                                    @if($tenantAddon && $tenantAddon->expires_at)
                                        <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                                            Valid till {{ $tenantAddon->expires_at->format('M d, Y') }}
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem; line-height: 1.5;">{{ $addon->subtitle ?? $addon->description }}</p>

                            @if($addon->description || $addon->image || $addon->video)
                                <div style="margin-bottom: 1rem; text-align: left;">
                                    <button
                                        onclick="openAddonModal({{ $addon->id }})"
                                        style="color: #615d40; font-size: 0.75rem; font-weight: 500; cursor: pointer; background: none; border: none; padding: 0;"
                                    >
                                        More →
                                    </button>
                                </div>
                            @endif

                            <div style="margin-top: 0.75rem;">
                                <div style="font-size: 1.5rem; font-weight: 700; line-height: 1;">${{ number_format($addon->price, 2) }}</div>
                                <div style="font-size: 0.75rem; color: #9ca3af;">per month</div>
                            </div>
                        </div>

                        <div style="flex-shrink: 0;">
                            @if($isSubscribed)
                                <x-filament::button
                                    wire:click="unsubscribe({{ $addon->id }})"
                                    wire:confirm="Are you sure you want to unsubscribe from {{ $addon->name }}? The addon will remain active until the end of your current subscription period. No refund will be issued, and it will not renew in the next cycle."
                                    color="danger"
                                    size="sm"
                                >
                                    Unsubscribe
                                </x-filament::button>
                            @else
                                <x-filament::button
                                    wire:click="subscribe({{ $addon->id }})"
                                    wire:confirm="Subscribe to {{ $addon->name }}? You will be charged a prorated amount for the remaining time in your current subscription period."
                                    size="sm"
                                >
                                    Subscribe
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                </x-filament::section>
            @endforeach
        </div>

        <x-filament::section icon="heroicon-o-information-circle" icon-color="info">
            <x-slot name="heading">
                About Add-ons
            </x-slot>

            <p class="text-sm">
                Add-ons will enhance your application with additional features. Subscriptions are processed with dummy payment and activated immediately.
            </p>
        </x-filament::section>
    @endif

    <!-- Addon Details Modal -->
    <div id="addonModal" style="display: none; position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 9999; align-items: center; justify-content: center;" onclick="closeAddonModal(event)">
        <div style="background: white; border-radius: 0.5rem; max-width: 42rem; width: 100%; margin: 1rem; max-height: 90vh; overflow-y: auto;" onclick="event.stopPropagation()">
            <div style="padding: 1.5rem;">
                <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
                    <button onclick="closeAddonModal()" style="color: #9ca3af; background: none; border: none; cursor: pointer; padding: 0;">
                        <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="modalAddonContent" style="display: flex; flex-direction: column; gap: 1rem;">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        const addonDetails = {};

        // Initialize addon data
        @foreach($addons as $addon)
            addonDetails[{{ $addon->id }}] = {
                name: '{{ addslashes($addon->name) }}',
                subtitle: '{{ addslashes($addon->subtitle ?? '') }}',
                description: {!! json_encode($addon->description) !!},
                image: '{{ $addon->image }}',
                video: '{{ $addon->video }}',
                price: {{ $addon->price }}
            };
        @endforeach

        function openAddonModal(addonId) {
            const addon = addonDetails[addonId];
            if (!addon) return;

            let content = '';

            // Add image if exists
            if (addon.image) {
                content += `<div style="margin-bottom: 1rem;">
                    <img src="/storage/${addon.image}" alt="${addon.name}" style="width: 100%; border-radius: 0.5rem;">
                </div>`;
            }

            // Add video if exists
            if (addon.video) {
                content += `<div style="margin-bottom: 1rem;">
                    <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                        <iframe src="${getEmbedUrl(addon.video)}" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 0.5rem;" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                </div>`;
            }

            // Add description if exists
            if (addon.description) {
                content += `<div style="color: #374151; white-space: pre-line;">${addon.description}</div>`;
            }

            document.getElementById('modalAddonContent').innerHTML = content;
            document.getElementById('addonModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeAddonModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('addonModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function getEmbedUrl(url) {
            // Convert YouTube URLs to embed format
            if (url.includes('youtube.com') || url.includes('youtu.be')) {
                const videoId = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i);
                if (videoId) {
                    return `https://www.youtube.com/embed/${videoId[1]}`;
                }
            }
            // Convert Vimeo URLs to embed format
            if (url.includes('vimeo.com')) {
                const videoId = url.match(/vimeo\.com\/(\d+)/);
                if (videoId) {
                    return `https://player.vimeo.com/video/${videoId[1]}`;
                }
            }
            return url;
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAddonModal();
            }
        });
    </script>
    @endscript
    </div>
</x-filament-panels::page>
