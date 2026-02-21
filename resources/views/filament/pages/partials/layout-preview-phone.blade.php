<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');
    .preview-scroll-assign::-webkit-scrollbar { width: 4px; }
    .preview-scroll-assign::-webkit-scrollbar-track { background: transparent; }
    .preview-scroll-assign::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.2); border-radius: 2px; }
</style>

<div style="position: relative; width: 375px; height: 600px; flex-shrink: 0;">
    <img src="{{ asset('img/apple-iphone-13-pro-max-2021-medium.png') }}"
         alt="iPhone Frame"
         style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 2; pointer-events: none;">

    <div style="position: absolute; top: 15px; left: 16px; right: 16px; bottom: 15px; background: white; border-radius: 40px; overflow: hidden; z-index: 1;">
        <div class="preview-scroll-assign" style="height: 100%; overflow-y: auto; overflow-x: hidden; background: #f9fafb; -webkit-overflow-scrolling: touch;">
            @if(!empty($layoutData))
                <div style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem;">
                    @foreach($layoutData as $block)
                        @if(!is_array($block) || empty($block['type']) || !array_key_exists('data', $block))
                            @continue
                        @endif

                        @if($block['type'] === 'banner')
                            @php
                                $imageUrl = $block['data']['image_url'] ?? null;
                                if (is_array($imageUrl) && !empty($imageUrl)) {
                                    $imageUrl = reset($imageUrl);
                                }
                            @endphp
                            <div style="position: relative; width: 100%; border-radius: 0.5rem; overflow: hidden;">
                                @if($imageUrl)
                                    <img src="{{ Storage::url($imageUrl) }}" alt="Banner" style="width: 100%; height: auto; display: block;">
                                @else
                                    <div style="width: 100%; min-height: 150px; background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: #9ca3af;">Banner</div>
                                @endif
                                @if(!empty($block['data']['title']))
                                    <div style="position: absolute; top: 0.5rem; right: 0.5rem; background: white; padding: 0.25rem 0.5rem; border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                        <span style="font-family: 'Montserrat', sans-serif; font-size: 0.625rem; font-weight: 600; color: #ef4444;">{{ $block['data']['title'] }}</span>
                                    </div>
                                @endif
                            </div>
                        @elseif($block['type'] === 'mini_banner')
                            @php
                                $miniBannerImg = $block['data']['image_url'] ?? null;
                                if (is_array($miniBannerImg) && !empty($miniBannerImg)) {
                                    $miniBannerImg = reset($miniBannerImg);
                                }
                            @endphp
                            @if($miniBannerImg)
                                <img src="{{ Storage::url($miniBannerImg) }}" alt="Mini Banner" style="width: 100%; height: auto; display: block; border-radius: 0.5rem;">
                            @else
                                <div style="width: 100%; min-height: 100px; background: #e5e7eb; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: #9ca3af;">Mini Banner</div>
                            @endif
                        @elseif($block['type'] === 'promo_card')
                            @php
                                $promoImg = $block['data']['image_url'] ?? null;
                                if (is_array($promoImg) && !empty($promoImg)) {
                                    $promoImg = reset($promoImg);
                                }
                            @endphp
                            <div style="background: #F0F0F0; border-radius: 0.5rem; overflow: hidden;">
                                @if($promoImg)
                                    <img src="{{ Storage::url($promoImg) }}" alt="Promo" style="width: 100%; height: auto; display: block;">
                                @else
                                    <div style="width: 100%; min-height: 120px; background: #F0F0F0; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: #9ca3af;">Promo Image</div>
                                @endif
                                <div style="padding: 1rem; text-align: center;">
                                    <h3 style="font-family: 'Montserrat', sans-serif; font-size: 14px; font-weight: 500; margin-bottom: 0.5rem; color: #000;">
                                        {{ $block['data']['title'] ?? 'Promo Title' }}
                                    </h3>
                                    @if(!empty($block['data']['text']))
                                        <p style="font-family: 'Montserrat', sans-serif; color: #6b7280; font-size: 10px; font-weight: 300; line-height: 1.3; margin-bottom: 0.5rem;">
                                            {{ $block['data']['text'] }}
                                        </p>
                                    @endif
                                    @if(!empty($block['data']['cta_label']))
                                        <div style="font-family: 'Montserrat', sans-serif; text-decoration: underline; font-weight: 500; font-size: 10px; color: #000;">
                                            {{ $block['data']['cta_label'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @elseif($block['type'] === 'product_list' || $block['type'] === 'new_releases')
                            <div>
                                @if(!empty($block['data']['title']))
                                    <h3 style="font-family: 'Montserrat', sans-serif; font-size: 14px; font-weight: 500; margin-bottom: 0.5rem; color: #000;">{{ $block['data']['title'] }}</h3>
                                @endif
                                <div style="display: flex; gap: 0.5rem; overflow-x: auto;">
                                    @if($block['type'] === 'product_list' && !empty($block['data']['products']))
                                        @php
                                            $shopify = app(\App\Services\Shopify\ShopifyStorefrontService::class);
                                            $tenant = auth()->user()->tenant;
                                        @endphp
                                        @foreach(array_slice($block['data']['products'], 0, 3) as $productData)
                                            @php
                                                $productId = $productData['product_id'] ?? null;
                                                $product = null;
                                                if ($productId && str_starts_with($productId, 'gid://shopify/Product/')) {
                                                    try {
                                                        $product = $shopify->getProductById($tenant, $productId);
                                                    } catch (\Exception $e) {}
                                                }
                                            @endphp
                                            <div style="background: white; border-radius: 0.5rem; overflow: hidden; padding: 0.5rem; min-width: 120px;">
                                                @if($product && !empty($product['images'][0]['url'] ?? null))
                                                    <img src="{{ $product['images'][0]['url'] }}" alt="{{ $product['title'] ?? 'Product' }}" style="width: 100%; aspect-ratio: 3/4; object-fit: cover; border-radius: 0.375rem; display: block; margin-bottom: 0.5rem;">
                                                @else
                                                    <div style="width: 100%; aspect-ratio: 3/4; background: #e5e7eb; border-radius: 0.375rem; margin-bottom: 0.5rem;"></div>
                                                @endif
                                                <h4 style="font-family: 'Montserrat', sans-serif; font-size: 11px; font-weight: 500; color: #000; text-align: center;">
                                                    {{ $product['title'] ?? 'Product' }}
                                                </h4>
                                                @if($product && !empty($product['priceRange']['minVariantPrice']['amount']))
                                                    <p style="font-family: 'Montserrat', sans-serif; font-size: 10px; font-weight: 600; color: #b45309; text-align: center;">
                                                        {{ number_format($product['priceRange']['minVariantPrice']['amount'], 0) }} {{ $product['priceRange']['minVariantPrice']['currencyCode'] }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endforeach
                                    @elseif($block['type'] === 'new_releases')
                                        @php
                                            $shopify = app(\App\Services\Shopify\ShopifyStorefrontService::class);
                                            $tenant = auth()->user()->tenant;
                                            $latestProducts = [];
                                            try {
                                                $count = $block['data']['count'] ?? 10;
                                                $latestProducts = $shopify->getLatestProducts($tenant, min($count, 3));
                                            } catch (\Exception $e) {}
                                        @endphp
                                        @if(!empty($latestProducts))
                                            @foreach($latestProducts as $product)
                                                <div style="background: white; border-radius: 0.5rem; overflow: hidden; padding: 0.5rem; min-width: 120px;">
                                                    @if(!empty($product['images'][0]['url'] ?? null))
                                                        <img src="{{ $product['images'][0]['url'] }}" alt="{{ $product['title'] ?? 'Product' }}" style="width: 100%; aspect-ratio: 3/4; object-fit: cover; border-radius: 0.375rem; display: block; margin-bottom: 0.5rem;">
                                                    @else
                                                        <div style="width: 100%; aspect-ratio: 3/4; background: #e5e7eb; border-radius: 0.375rem; margin-bottom: 0.5rem;"></div>
                                                    @endif
                                                    <h4 style="font-family: 'Montserrat', sans-serif; font-size: 11px; font-weight: 500; color: #000; text-align: center;">
                                                        {{ $product['title'] ?? 'Product' }}
                                                    </h4>
                                                    @if(!empty($product['priceRange']['minVariantPrice']['amount']))
                                                        <p style="font-family: 'Montserrat', sans-serif; font-size: 10px; font-weight: 600; color: #b45309; text-align: center;">
                                                            {{ number_format($product['priceRange']['minVariantPrice']['amount'], 0) }} {{ $product['priceRange']['minVariantPrice']['currencyCode'] }}
                                                        </p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @elseif($block['type'] === 'category_grid')
                            <div>
                                @if(!empty($block['data']['title']))
                                    <h3 style="font-family: 'Montserrat', sans-serif; font-size: 12px; font-weight: 600; margin-bottom: 0.5rem; color: #000;">{{ $block['data']['title'] }}</h3>
                                @endif
                                <div style="display: flex; gap: 0.5rem; overflow-x: auto;">
                                    @if(!empty($block['data']['categories']))
                                        @foreach(array_slice($block['data']['categories'], 0, 4) as $category)
                                            @php
                                                $catImageUrl = $category['image_url'] ?? null;
                                                if (is_array($catImageUrl) && !empty($catImageUrl)) {
                                                    $catImageUrl = reset($catImageUrl);
                                                }
                                            @endphp
                                            <div style="position: relative; border-radius: 0.5rem; overflow: hidden; min-width: 80px; height: 120px;">
                                                @if($catImageUrl)
                                                    <img src="{{ Storage::url($catImageUrl) }}" alt="{{ $category['title'] ?? 'Category' }}" style="width: 100%; height: 100%; object-fit: cover;">
                                                @else
                                                    <div style="width: 100%; height: 100%; background: #e5e7eb;"></div>
                                                @endif
                                                <div style="position: absolute; inset: 0; display: flex; align-items: flex-end; justify-content: center; background: rgba(0,0,0,0.3);">
                                                    <p style="font-family: 'Montserrat', sans-serif; font-size: 9px; font-weight: 500; color: white; padding: 0.25rem; text-align: center;">{{ $category['title'] ?? 'Category' }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @else
                            <div style="background: white; border-radius: 0.5rem; padding: 1rem; text-align: center;">
                                <p style="font-family: 'Montserrat', sans-serif; font-size: 0.75rem; color: #6b7280;">{{ ucfirst(str_replace('_', ' ', $block['type'])) }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div style="text-align: center; padding: 2rem 1rem; color: #9ca3af;">
                    <p style="font-family: 'Montserrat', sans-serif; font-size: 0.875rem;">No layout content</p>
                </div>
            @endif
        </div>
    </div>
</div>
