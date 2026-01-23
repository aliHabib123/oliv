<div x-data="{ isUpdating: false }"
     @preview-updating.window="isUpdating = true; setTimeout(() => isUpdating = false, 100)">
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <x-heroicon-o-eye style="width: 1.25rem; height: 1.25rem;" />
                <span>Live Preview</span>
                <span x-show="!isUpdating" style="background: #10b981; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600;">
                    ⚡ LIVE: {{ count($layoutData ?? []) }} blocks | {{ now()->format('H:i:s') }}
                </span>
                <span x-show="isUpdating" x-cloak style="background: #3b82f6; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem;">
                    <svg style="width: 0.875rem; height: 0.875rem; animation: spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Updating...
                </span>
            </div>
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
        </x-slot>

        <div style="display: flex; justify-content: center; align-items: center; padding: 2rem; background: #f3f4f6; border-radius: 0.5rem; height: 850px; overflow: hidden;">
            {{-- iPhone Mockup Container --}}
            <div style="position: relative; width: 375px; height: 812px; flex-shrink: 0;">
                {{-- iPhone Frame Image --}}
                <img src="{{ asset('img/apple-iphone-13-pro-max-2021-medium.png') }}"
                     alt="iPhone Frame"
                     style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 2; pointer-events: none;">

                {{-- Screen Content Area --}}
                <div style="position: absolute; top: 15px; left: 14px; right: 14px; bottom: 15px; background: white; border-radius: 40px; overflow-y: scroll; z-index: 1;">
                    <div class="phone-scroll" style="height: 100%; max-height: 100%; overflow-y: scroll; overflow-x: hidden; background: #f9fafb; transition: opacity 0.2s; -webkit-overflow-scrolling: touch;" :style="isUpdating ? 'opacity: 0.6' : ''">
                        @if(!empty($layoutData))
                            <div style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem;">
                                @foreach($layoutData as $block)
                        @if($block['type'] === 'banner')
                            {{-- Banner Preview - Full Width with Title Badge --}}
                            @php
                                $imageUrl = $block['data']['image_url'] ?? null;
                                if (is_array($imageUrl) && !empty($imageUrl)) {
                                    $imageUrl = reset($imageUrl);
                                }
                            @endphp
                            <div style="position: relative; width: 100%; border-radius: 0.5rem; overflow: hidden;">
                                @if($imageUrl)
                                    <img src="{{ Storage::url($imageUrl) }}"
                                         alt="Banner"
                                         style="width: 100%; height: auto; display: block;">
                                @else
                                    <div style="width: 100%; min-height: 200px; background: #e5e7eb; display: flex; align-items: center; justify-content: center;">
                                        <x-heroicon-o-photo style="width: 3rem; height: 3rem; color: #9ca3af;" />
                                    </div>
                                @endif
                                @if(!empty($block['data']['title']))
                                    <div style="position: absolute; top: 1rem; right: 1rem; background: white; padding: 0.5rem 1rem; border-radius: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                        <span style="font-family: 'Montserrat', sans-serif; font-size: 0.875rem; font-weight: 600; color: #ef4444;">{{ $block['data']['title'] }}</span>
                                    </div>
                                @endif
                            </div>

                        @elseif($block['type'] === 'mini_banner')
                            {{-- Mini Banner Preview - Full Width Mobile Design --}}
                            @php
                                $miniBannerImg = $block['data']['image_url'] ?? null;
                                if (is_array($miniBannerImg) && !empty($miniBannerImg)) {
                                    $miniBannerImg = reset($miniBannerImg);
                                }
                            @endphp
                            @if($miniBannerImg)
                                <img src="{{ Storage::url($miniBannerImg) }}"
                                     alt="Mini Banner"
                                     style="width: 100%; height: auto; display: block; border-radius: 0.5rem;">
                            @else
                                <div style="width: 100%; min-height: 150px; background: #e5e7eb; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                                    <x-heroicon-o-photo style="width: 3rem; height: 3rem; color: #9ca3af;" />
                                </div>
                            @endif

                        @elseif($block['type'] === 'promo_card')
                            {{-- Promo Card Preview - Banner Style --}}
                            @php
                                $promoImg = $block['data']['image_url'] ?? null;
                                if (is_array($promoImg) && !empty($promoImg)) {
                                    $promoImg = reset($promoImg);
                                }
                            @endphp
                            <div style="background: #F0F0F0; border-radius: 0.5rem; overflow: hidden;">
                                @if($promoImg)
                                    <img src="{{ Storage::url($promoImg) }}"
                                         alt="Promo"
                                         style="width: 100%; height: auto; display: block;">
                                @else
                                    <div style="width: 100%; min-height: 200px; background: #F0F0F0; display: flex; align-items: center; justify-content: center;">
                                        <x-heroicon-o-photo style="width: 3rem; height: 3rem; color: #9ca3af;" />
                                    </div>
                                @endif
                                <div style="padding: 1.5rem; text-align: center;">
                                    <h3 style="font-family: 'Montserrat', sans-serif; font-size: 20px; font-weight: 500; margin-bottom: 1rem; color: #000;">
                                        {{ $block['data']['title'] ?? 'Promo Title' }}
                                    </h3>
                                    @if(!empty($block['data']['text']))
                                        <p style="font-family: 'Montserrat', sans-serif; color: #6b7280; font-size: 12px; font-weight: 300; line-height: 1.5; margin-bottom: 1rem;">
                                            {{ $block['data']['text'] }}
                                        </p>
                                    @endif
                                    @if(!empty($block['data']['cta_label']))
                                        <div style="font-family: 'Montserrat', sans-serif; text-decoration: underline; font-weight: 500; font-size: 12px; color: #000; cursor: pointer;">
                                            {{ $block['data']['cta_label'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                        @elseif($block['type'] === 'products_grid')
                            {{-- Products Grid Preview --}}
                            <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <h3 style="font-family: 'Montserrat', sans-serif; font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem;">
                                    {{ $block['data']['title'] ?? 'Products Grid' }}
                                </h3>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.75rem;">
                                    @if(!empty($block['data']['products']))
                                        @php
                                            $shopify = app(\App\Services\Shopify\ShopifyStorefrontService::class);
                                        @endphp
                                        @foreach(array_slice($block['data']['products'], 0, 4) as $productData)
                                            @php
                                                $productId = $productData['product_id'] ?? null;
                                                $product = null;
                                                if ($productId && str_starts_with($productId, 'gid://shopify/Product/')) {
                                                    try {
                                                        $product = $shopify->getProductById($tenant, $productId);
                                                    } catch (\Exception $e) {}
                                                }
                                            @endphp
                                            <div style="background: #f9fafb; border-radius: 0.375rem; padding: 0.75rem; text-align: center;">
                                                @if($product && !empty($product['images'][0]['url'] ?? null))
                                                    <img src="{{ $product['images'][0]['url'] }}"
                                                         alt="{{ $product['title'] ?? 'Product' }}"
                                                         style="width: 100%; height: 80px; object-fit: cover; border-radius: 0.375rem; margin-bottom: 0.5rem;">
                                                @else
                                                    <div style="width: 100%; height: 80px; background: #e5e7eb; border-radius: 0.375rem; margin-bottom: 0.5rem;"></div>
                                                @endif
                                                <p style="font-family: 'Montserrat', sans-serif; font-size: 0.75rem; font-weight: 600; color: #374151;">
                                                    {{ $product['title'] ?? 'Product' }}
                                                </p>
                                            </div>
                                        @endforeach
                                    @else
                                        <p style="font-family: 'Montserrat', sans-serif; color: #9ca3af; font-size: 0.875rem;">No products added</p>
                                    @endif
                                </div>
                            </div>

                        @elseif($block['type'] === 'product_list')
                            {{-- Product List Preview - Horizontal Carousel --}}
                            <div style="margin-top: 2rem;">
                                @if(!empty($block['data']['title']))
                                    <h3 style="font-family: 'Montserrat', sans-serif; font-size: 20px; font-weight: 500; margin-bottom: 1rem; color: #000;">
                                        {{ $block['data']['title'] }}
                                    </h3>
                                @endif
                                <div style="display: flex; gap: 1rem; overflow-x: auto; scroll-snap-type: x mandatory; padding-bottom: 0.5rem; margin-left: -1rem; padding-left: 1rem; margin-right: -1rem; padding-right: 1rem;">
                                    @if(!empty($block['data']['products']))
                                        @php
                                            $shopify = app(\App\Services\Shopify\ShopifyStorefrontService::class);
                                        @endphp
                                        @foreach(array_slice($block['data']['products'], 0, 4) as $productData)
                                            @php
                                                $productId = $productData['product_id'] ?? null;
                                                $product = null;
                                                if ($productId && str_starts_with($productId, 'gid://shopify/Product/')) {
                                                    try {
                                                        $product = $shopify->getProductById($tenant, $productId);
                                                    } catch (\Exception $e) {}
                                                }
                                            @endphp
                                            <div style="background: white; border-radius: 0.5rem; overflow: hidden; padding: 0.75rem; flex: 0 0 52%; scroll-snap-align: start;">
                                                <div style="position: relative; margin-bottom: 0.75rem;">
                                                    @if($product && !empty($product['images'][0]['url'] ?? null))
                                                        <img src="{{ $product['images'][0]['url'] }}"
                                                             alt="{{ $product['title'] ?? 'Product' }}"
                                                             style="width: 100%; aspect-ratio: 3/4; object-fit: cover; border-radius: 0.375rem; display: block;">
                                                    @else
                                                        <div style="width: 100%; aspect-ratio: 3/4; background: #e5e7eb; border-radius: 0.375rem;"></div>
                                                    @endif
                                                    <div style="position: absolute; top: 0.5rem; right: 2rem;">
                                                        <svg style="width: 2rem; height: 2rem; color: #b45309; fill: none; stroke: currentColor; stroke-width: 1;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                {{-- <p style="font-family: 'Montserrat', sans-serif; font-size: 0.75rem; font-weight: 600; color: #ef4444; margin-bottom: 0.25rem; text-align: center;">
                                                    BIG SALE
                                                </p> --}}
                                                <h4 style="font-family: 'Montserrat', sans-serif; font-size: 18px; font-weight: 500; color: #000; margin-bottom: 0.5rem; text-align: center;">
                                                    {{ $product['title'] ?? 'Product' }}
                                                </h4>
                                                @if($product && !empty($product['description']))
                                                    <p style="font-family: 'Montserrat', sans-serif; font-size: 0.75rem; color: #6b7280; margin-bottom: 0.5rem; text-align: center; line-height: 1.5;">
                                                        {{ \Illuminate\Support\Str::limit(strip_tags($product['description']), 60) }}
                                                    </p>
                                                @endif
                                                @if($product && !empty($product['priceRange']['minVariantPrice']['amount']))
                                                    <p style="font-family: 'Montserrat', sans-serif; font-size: 1rem; font-weight: 600; color: #b45309; text-align: center;">
                                                        {{ number_format($product['priceRange']['minVariantPrice']['amount'], 0) }} {{ $product['priceRange']['minVariantPrice']['currencyCode'] }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        <p style="font-family: 'Montserrat', sans-serif; color: #9ca3af; font-size: 0.875rem;">No products added</p>
                                    @endif
                                </div>
                            </div>

                        @elseif($block['type'] === 'new_releases')
                            {{-- New Releases Preview - Horizontal Carousel --}}
                            <div style="margin-top: 2rem;">
                                @if(!empty($block['data']['title']))
                                    <h3 style="font-family: 'Montserrat', sans-serif; font-size: 20px; font-weight: 500; margin-bottom: 1rem; color: #000;">
                                        {{ $block['data']['title'] }}
                                    </h3>
                                @endif
                                <div style="display: flex; gap: 1rem; overflow-x: auto; scroll-snap-type: x mandatory; padding-bottom: 0.5rem; margin-left: -1rem; padding-left: 1rem; margin-right: -1rem; padding-right: 1rem;">
                                    @php
                                        $shopify = app(\App\Services\Shopify\ShopifyStorefrontService::class);
                                        $latestProducts = [];
                                        try {
                                            $count = $block['data']['count'] ?? 10;
                                            $latestProducts = $shopify->getLatestProducts($tenant, min($count, 10));
                                        } catch (\Exception $e) {}
                                    @endphp
                                    @if(!empty($latestProducts))
                                        @foreach(array_slice($latestProducts, 0, 4) as $product)
                                            <div style="background: white; border-radius: 0.5rem; overflow: hidden; padding: 0.75rem; flex: 0 0 52%; scroll-snap-align: start;">
                                                <div style="position: relative; margin-bottom: 0.75rem;">
                                                    @if(!empty($product['images'][0]['url'] ?? null))
                                                        <img src="{{ $product['images'][0]['url'] }}"
                                                             alt="{{ $product['title'] ?? 'Product' }}"
                                                             style="width: 100%; aspect-ratio: 3/4; object-fit: cover; border-radius: 0.375rem; display: block;">
                                                    @else
                                                        <div style="width: 100%; aspect-ratio: 3/4; background: #e5e7eb; border-radius: 0.375rem;"></div>
                                                    @endif
                                                    <div style="position: absolute; top: 0.5rem; right: 2rem;">
                                                        <svg style="width: 2rem; height: 2rem; color: #b45309; fill: none; stroke: currentColor; stroke-width: 1;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                <h4 style="font-family: 'Montserrat', sans-serif; font-size: 18px; font-weight: 500; color: #000; margin-bottom: 0.5rem; text-align: center;">
                                                    {{ $product['title'] ?? 'Product' }}
                                                </h4>
                                                @if(!empty($product['description']))
                                                    <p style="font-family: 'Montserrat', sans-serif; font-size: 0.75rem; color: #6b7280; margin-bottom: 0.5rem; text-align: center; line-height: 1.5;">
                                                        {{ \Illuminate\Support\Str::limit(strip_tags($product['description']), 60) }}
                                                    </p>
                                                @endif
                                                @if(!empty($product['priceRange']['minVariantPrice']['amount']))
                                                    <p style="font-family: 'Montserrat', sans-serif; font-size: 1rem; font-weight: 600; color: #b45309; text-align: center;">
                                                        {{ number_format($product['priceRange']['minVariantPrice']['amount'], 0) }} {{ $product['priceRange']['minVariantPrice']['currencyCode'] }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        <p style="font-family: 'Montserrat', sans-serif; color: #9ca3af; font-size: 0.875rem;">No new releases available</p>
                                    @endif
                                </div>
                            </div>

                        @elseif($block['type'] === 'category_grid')
                            {{-- Category Grid Preview - Horizontal Scroll with 3+ Categories --}}
                            <div style="margin-top: 2rem;">
                                @if(!empty($block['data']['title']))
                                    <h3 style="font-family: 'Montserrat', sans-serif; font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem; color: #000;">
                                        {{ $block['data']['title'] }}
                                    </h3>
                                @endif
                                <div style="display: flex; gap: 1rem; overflow-x: auto; scroll-snap-type: x mandatory; padding-bottom: 0.5rem; margin-left: -1rem; padding-left: 1rem; margin-right: -1rem; padding-right: 1rem;">
                                    @if(!empty($block['data']['categories']))
                                        @foreach(array_slice($block['data']['categories'], 0, 8) as $category)
                                            @php
                                                $catImageUrl = $category['image_url'] ?? null;
                                                if (is_array($catImageUrl) && !empty($catImageUrl)) {
                                                    $catImageUrl = reset($catImageUrl);
                                                }
                                            @endphp
                                            <div style="position: relative; border-radius: 1rem; overflow: hidden; aspect-ratio: 3/4; flex: 0 0 30%; scroll-snap-align: start;">
                                                @if($catImageUrl)
                                                    <img src="{{ Storage::url($catImageUrl) }}"
                                                         alt="{{ $category['title'] ?? 'Category' }}"
                                                         style="width: 100%; height: 100%; object-fit: cover; display: block;">
                                                @else
                                                    <div style="width: 100%; height: 100%; background: #e5e7eb;"></div>
                                                @endif
                                                <div style="position: absolute; inset: 0; display: flex; align-items: flex-end; justify-content: center; background: rgba(0,0,0,0.2);">
                                                    <p style="font-family: 'Montserrat', sans-serif; font-size: 16px; font-weight: 500; color: white; text-transform: uppercase; text-align: center; padding: 1rem; text-decoration: underline;">
                                                        {{ $category['title'] ?? 'Category' }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <p style="font-family: 'Montserrat', sans-serif; color: #9ca3af; font-size: 0.875rem;">No categories added</p>
                                    @endif
                                </div>
                            </div>

                        @endif
                    @endforeach
                            </div>
                        @else
                            <div style="text-align: center; padding: 3rem 1rem; color: #9ca3af;">
                                <x-heroicon-o-cube-transparent style="width: 4rem; height: 4rem; margin: 0 auto 0.75rem; color: #d1d5db;" />
                                <p style="font-family: 'Montserrat', sans-serif; font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem;">No content yet</p>
                                <p style="font-family: 'Montserrat', sans-serif; font-size: 0.875rem;">Start building your home layout by adding blocks</p>
                            </div>
                        @endif
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
