<div wire:ignore x-data="{
        isUpdating: false,
        layoutData: @js($layoutData ?? []),

        init() {
            Livewire.on('preview-updated', (eventData) => {
                const data = Array.isArray(eventData) ? eventData[0]?.data : eventData.data;
                this.isUpdating = true;
                this.layoutData = Array.isArray(data) ? [...data] : [];
                this.$nextTick(() => {
                    this.isUpdating = false;
                });
            });
        },

        getImageUrl(imageUrl) {
            if (!imageUrl) return null;

            let path = imageUrl;

            // Unwrap UUID-keyed array
            if (typeof imageUrl === 'object' && !Array.isArray(imageUrl)) {
                const values = Object.values(imageUrl);
                path = values[0];
            }

            if (!path || typeof path !== 'string') return null;

            // Full URLs (temp files) or storage paths (saved)
            if (path.startsWith('http')) return path;
            if (path.startsWith('/storage/')) return path;
            return '/storage/' + path;
        }
    }"
     @preview-updating.window="isUpdating = true">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        [x-cloak] { display: none !important; }

        /* Custom scrollbar for phone preview */
        .phone-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .phone-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .phone-scroll::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 2px;
        }
        .phone-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.3);
        }
    </style>

    @php
        $dummyCurrency = 'AED';
        $dummyProducts = [
            [
                'title' => 'Classic Linen Shirt',
                'description' => 'Lightweight linen with a relaxed fit.',
                'price' => 189,
                'color' => '#1f2937',
            ],
            [
                'title' => 'Everyday Sneakers',
                'description' => 'Comfort sole, clean minimal upper.',
                'price' => 299,
                'color' => '#b45309',
            ],
            [
                'title' => 'Citrus Body Mist',
                'description' => 'Fresh notes with long-lasting wear.',
                'price' => 89,
                'color' => '#065f46',
            ],
            [
                'title' => 'Soft Knit Sweater',
                'description' => 'Warm, soft knit for cooler days.',
                'price' => 249,
                'color' => '#6d28d9',
            ],
            [
                'title' => 'Hydrating Face Serum',
                'description' => 'Daily hydration with a dewy finish.',
                'price' => 129,
                'color' => '#0e7490',
            ],
            [
                'title' => 'Leather Card Wallet',
                'description' => 'Slim profile, premium leather.',
                'price' => 149,
                'color' => '#7f1d1d',
            ],
        ];

        $dummyCategories = [
            ['title' => 'New In', 'color' => '#111827'],
            ['title' => 'Best Sellers', 'color' => '#b45309'],
            ['title' => 'Skincare', 'color' => '#0f766e'],
            ['title' => 'Shoes', 'color' => '#4338ca'],
            ['title' => 'Accessories', 'color' => '#7c2d12'],
            ['title' => 'Sale', 'color' => '#be123c'],
        ];

        $dummyImage = function (string $title, string $color): string {
            $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='600' height='800' viewBox='0 0 600 800'>" .
                "<defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'>" .
                "<stop offset='0' stop-color='" . $color . "' stop-opacity='0.95'/>" .
                "<stop offset='1' stop-color='#000000' stop-opacity='0.15'/></linearGradient></defs>" .
                "<rect width='600' height='800' fill='url(#g)'/>" .
                "<rect x='42' y='52' width='516' height='696' rx='24' fill='rgba(255,255,255,0.10)'/>" .
                "<text x='70' y='120' fill='white' font-family='Montserrat, Arial, sans-serif' font-weight='700' font-size='34'>" .
                htmlspecialchars(mb_strtoupper(mb_substr($title, 0, 18))) .
                "</text>" .
                "<text x='70' y='165' fill='rgba(255,255,255,0.85)' font-family='Montserrat, Arial, sans-serif' font-weight='400' font-size='18'>Preview item</text>" .
                "</svg>";

            return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
        };

        $industryKey = $industry ?: 'other';
        $industryProductsDir = public_path('img/template-preview/' . $industryKey . '/products');
        $industryProductsUrl = 'img/template-preview/' . $industryKey . '/products';
        $industryProductImage = function (int $index, string $title, string $color) use ($industryProductsDir, $industryProductsUrl, $dummyImage): string {
            $candidates = [
                $index . '.jpg',
                $index . '.jpeg',
                $index . '.png',
                $index . '.webp',
            ];

            foreach ($candidates as $file) {
                $absolute = $industryProductsDir . DIRECTORY_SEPARATOR . $file;
                if (is_string($absolute) && file_exists($absolute)) {
                    return asset($industryProductsUrl . '/' . $file);
                }
            }

            return $dummyImage($title, $color);
        };
    @endphp

    <x-filament::section>
        <div style="display: flex; justify-content: center; align-items: center; padding: 2rem; background: #f3f4f6; border-radius: 0.5rem; height: 770px; overflow: hidden;">
            {{-- iPhone Mockup Container --}}
            <div style="position: relative; width: 375px; height: 730px; flex-shrink: 0;">
                {{-- iPhone Frame Image --}}
                <img src="{{ asset('img/apple-iphone-13-pro-max-2021-medium.png') }}"
                     alt="iPhone Frame"
                     style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 2; pointer-events: none;">

                {{-- Screen Content Area --}}
                <div style="position: absolute; top: 15px; left: 16px; right: 16px; bottom: 15px; background: white; border-radius: 40px; overflow-y: scroll; z-index: 1;">
                    <div class="phone-scroll" style="height: 100%; max-height: 100%; overflow-y: scroll; overflow-x: hidden; background: #f9fafb; transition: opacity 0.2s; -webkit-overflow-scrolling: touch;" :style="isUpdating ? 'opacity: 0.6' : ''">
                        <template x-if="layoutData && layoutData.length > 0">
                            <div style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem;">
                                <template x-for="(block, blockIndex) in layoutData" :key="blockIndex">
                                    <div>
                        <template x-if="block.type === 'banner'">
                            <div style="position: relative; width: 100%; border-radius: 0.5rem; overflow: hidden;">
                                <template x-if="getImageUrl(block.data?.image_url)">
                                    <img :src="getImageUrl(block.data?.image_url)"
                                         alt="Banner"
                                         style="width: 100%; height: auto; display: block;">
                                </template>
                                <template x-if="!getImageUrl(block.data?.image_url)">
                                    <div style="width: 100%; min-height: 200px; background: #e5e7eb; display: flex; align-items: center; justify-content: center;">
                                        <svg style="width: 3rem; height: 3rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                </template>
                                <template x-if="block.data?.title">
                                    <div style="position: absolute; top: 1rem; right: 1rem; background: white; padding: 0.5rem 1rem; border-radius: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                        <span style="font-family: 'Montserrat', sans-serif; font-size: 0.875rem; font-weight: 600; color: #ef4444;" x-text="block.data.title"></span>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="block.type === 'mini_banner'">
                            <div>
                                <template x-if="getImageUrl(block.data?.image_url)">
                                    <img :src="getImageUrl(block.data?.image_url)"
                                         alt="Mini Banner"
                                         style="width: 100%; height: auto; display: block; border-radius: 0.5rem;">
                                </template>
                                <template x-if="!getImageUrl(block.data?.image_url)">
                                    <div style="width: 100%; min-height: 150px; background: #e5e7eb; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                                        <svg style="width: 3rem; height: 3rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="block.type === 'promo_card'">
                            <div style="background: #F0F0F0; border-radius: 0.5rem; overflow: hidden;">
                                <template x-if="getImageUrl(block.data?.image_url)">
                                    <img :src="getImageUrl(block.data?.image_url)"
                                         alt="Promo"
                                         style="width: 100%; height: auto; display: block;">
                                </template>
                                <template x-if="!getImageUrl(block.data?.image_url)">
                                    <div style="width: 100%; min-height: 200px; background: #F0F0F0; display: flex; align-items: center; justify-content: center;">
                                        <svg style="width: 3rem; height: 3rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                </template>
                                <div style="padding: 1.5rem; text-align: center;">
                                    <h3 style="font-family: 'Montserrat', sans-serif; font-size: 20px; font-weight: 500; margin-bottom: 1rem; color: #000;" x-text="block.data?.title || 'Promo Title'"></h3>
                                    <template x-if="block.data?.text">
                                        <p style="font-family: 'Montserrat', sans-serif; color: #6b7280; font-size: 12px; font-weight: 300; line-height: 1.5; margin-bottom: 1rem;" x-text="block.data.text"></p>
                                    </template>
                                    <template x-if="block.data?.cta_label">
                                        <div style="font-family: 'Montserrat', sans-serif; text-decoration: underline; font-weight: 500; font-size: 12px; color: #000; cursor: pointer;" x-text="block.data.cta_label"></div>
                                    </template>
                                </div>
                            </div>
                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="!layoutData || layoutData.length === 0">
                            <div style="text-align: center; padding: 3rem 1rem; color: #9ca3af;">
                                <svg style="width: 4rem; height: 4rem; margin: 0 auto 0.75rem; color: #d1d5db;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                <p style="font-family: 'Montserrat', sans-serif; font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem;">No content yet</p>
                                <p style="font-family: 'Montserrat', sans-serif; font-size: 0.875rem;">Start building your home layout by adding blocks</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 1rem; padding: 0.75rem; background: #eff6ff; border-radius: 0.375rem; border-left: 4px solid #3b82f6;">
            <p style="font-family: 'Montserrat', sans-serif; font-size: 0.75rem; color: #1e40af;">
                <strong>Tip:</strong> Changes are reflected in real-time. The preview updates automatically as you edit.
            </p>
        </div>
    </x-filament::section>
</div>
