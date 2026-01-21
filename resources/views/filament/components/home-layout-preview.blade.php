<div>
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <x-heroicon-o-eye style="width: 1.25rem; height: 1.25rem;" />
                <span>Live Preview</span>
            </div>
        </x-slot>

        <div style="background: #f9fafb; border-radius: 0.5rem; padding: 1rem; min-height: 400px;">
            @if(!empty($layoutData))
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    @foreach($layoutData as $block)
                        @if($block['type'] === 'banner')
                            {{-- Banner Preview --}}
                            <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    @php
                                        $imageUrl = $block['data']['image_url'] ?? null;
                                        if (is_array($imageUrl)) {
                                            $imageUrl = $imageUrl[0] ?? null;
                                        }
                                    @endphp
                                    @if($imageUrl)
                                        <img src="{{ Storage::url($imageUrl) }}"
                                             alt="Banner"
                                             style="width: 120px; height: 120px; object-fit: cover; border-radius: 0.5rem;">
                                    @else
                                        <div style="width: 120px; height: 120px; background: #e5e7eb; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                                            <x-heroicon-o-photo style="width: 3rem; height: 3rem; color: #9ca3af;" />
                                        </div>
                                    @endif
                                    <div style="flex: 1;">
                                        <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
                                            {{ $block['data']['title'] ?? 'Banner Title' }}
                                        </h3>
                                        <p style="color: #6b7280; margin-bottom: 0.75rem;">
                                            {{ $block['data']['subtitle'] ?? 'Banner subtitle' }}
                                        </p>
                                        @if(!empty($block['data']['cta_label']))
                                            <button style="background: #f59e0b; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 600; border: none; cursor: pointer;">
                                                {{ $block['data']['cta_label'] }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                        @elseif($block['type'] === 'mini_banner')
                            {{-- Mini Banner Preview --}}
                            <div style="background: white; border-radius: 0.5rem; padding: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                @php
                                    $miniBannerImg = $block['data']['image_url'] ?? null;
                                    if (is_array($miniBannerImg)) {
                                        $miniBannerImg = $miniBannerImg[0] ?? null;
                                    }
                                @endphp
                                @if($miniBannerImg)
                                    <img src="{{ Storage::url($miniBannerImg) }}"
                                         alt="Mini Banner"
                                         style="width: 100%; height: 120px; object-fit: cover; border-radius: 0.375rem;">
                                @else
                                    <div style="width: 100%; height: 120px; background: #e5e7eb; border-radius: 0.375rem; display: flex; align-items: center; justify-content: center;">
                                        <x-heroicon-o-photo style="width: 3rem; height: 3rem; color: #9ca3af;" />
                                    </div>
                                @endif
                                @if(!empty($block['data']['url']))
                                    <p style="font-size: 0.75rem; color: #6b7280; margin-top: 0.5rem;">Link: {{ $block['data']['url'] }}</p>
                                @endif
                            </div>

                        @elseif($block['type'] === 'promo_card')
                            {{-- Promo Card Preview --}}
                            <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                @php
                                    $promoImg = $block['data']['image_url'] ?? null;
                                    if (is_array($promoImg)) {
                                        $promoImg = $promoImg[0] ?? null;
                                    }
                                @endphp
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    @if($promoImg)
                                        <img src="{{ Storage::url($promoImg) }}"
                                             alt="Promo"
                                             style="width: 100px; height: 100px; object-fit: cover; border-radius: 0.5rem;">
                                    @else
                                        <div style="width: 100px; height: 100px; background: #e5e7eb; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                                            <x-heroicon-o-photo style="width: 2.5rem; height: 2.5rem; color: #9ca3af;" />
                                        </div>
                                    @endif
                                    <div style="flex: 1;">
                                        <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 0.5rem;">
                                            {{ $block['data']['title'] ?? 'Promo Title' }}
                                        </h3>
                                        @if(!empty($block['data']['text']))
                                            <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                                {{ $block['data']['text'] }}
                                            </p>
                                        @endif
                                        @if(!empty($block['data']['cta_label']))
                                            <button style="background: #f59e0b; color: white; padding: 0.375rem 0.875rem; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 600; border: none; cursor: pointer;">
                                                {{ $block['data']['cta_label'] }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                        @elseif($block['type'] === 'products_grid')
                            {{-- Products Grid Preview --}}
                            <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem;">
                                    {{ $block['data']['title'] ?? 'Products Grid' }}
                                </h3>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.75rem;">
                                    @if(!empty($block['data']['products']))
                                        @foreach(array_slice($block['data']['products'], 0, 4) as $product)
                                            <div style="background: #f9fafb; border-radius: 0.375rem; padding: 0.75rem; text-align: center;">
                                                <div style="width: 100%; height: 80px; background: #e5e7eb; border-radius: 0.375rem; margin-bottom: 0.5rem;"></div>
                                                <p style="font-size: 0.75rem; font-weight: 600; color: #374151;">Product</p>
                                            </div>
                                        @endforeach
                                    @else
                                        <p style="color: #9ca3af; font-size: 0.875rem;">No products added</p>
                                    @endif
                                </div>
                            </div>

                        @elseif($block['type'] === 'product_list')
                            {{-- Product List Preview --}}
                            <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem;">
                                    {{ $block['data']['title'] ?? 'Product List' }}
                                </h3>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.75rem;">
                                    @if(!empty($block['data']['products']))
                                        @foreach(array_slice($block['data']['products'], 0, 4) as $product)
                                            <div style="background: #f9fafb; border-radius: 0.375rem; padding: 0.75rem; text-align: center;">
                                                <div style="width: 100%; height: 80px; background: #e5e7eb; border-radius: 0.375rem; margin-bottom: 0.5rem;"></div>
                                                <p style="font-size: 0.75rem; font-weight: 600; color: #374151;">Product</p>
                                            </div>
                                        @endforeach
                                    @else
                                        <p style="color: #9ca3af; font-size: 0.875rem;">No products added</p>
                                    @endif
                                </div>
                            </div>

                        @elseif($block['type'] === 'new_releases')
                            {{-- New Releases Preview --}}
                            <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem;">
                                    {{ $block['data']['title'] ?? 'New Releases' }}
                                </h3>
                                @if(!empty($block['data']['count']))
                                    <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.75rem;">
                                        Showing {{ $block['data']['count'] }} latest products
                                    </p>
                                @endif
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.75rem;">
                                    @for($i = 0; $i < min($block['data']['count'] ?? 4, 4); $i++)
                                        <div style="background: #f9fafb; border-radius: 0.375rem; padding: 0.75rem; text-align: center;">
                                            <div style="width: 100%; height: 80px; background: #e5e7eb; border-radius: 0.375rem; margin-bottom: 0.5rem;"></div>
                                            <p style="font-size: 0.75rem; font-weight: 600; color: #374151;">New Product</p>
                                        </div>
                                    @endfor
                                </div>
                                <p style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.75rem; font-style: italic;">
                                    App will fetch latest products from Shopify
                                </p>
                            </div>

                        @elseif($block['type'] === 'category_grid')
                            {{-- Category Grid Preview --}}
                            <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem;">
                                    {{ $block['data']['title'] ?? 'Categories' }}
                                </h3>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.75rem;">
                                    @if(!empty($block['data']['categories']))
                                        @foreach(array_slice($block['data']['categories'], 0, 4) as $category)
                                            <div style="background: #f9fafb; border-radius: 0.375rem; padding: 0.75rem; text-align: center;">
                                                @php
                                                    $catImageUrl = $category['image_url'] ?? null;
                                                    if (is_array($catImageUrl)) {
                                                        $catImageUrl = $catImageUrl[0] ?? null;
                                                    }
                                                @endphp
                                                @if($catImageUrl)
                                                    <img src="{{ Storage::url($catImageUrl) }}"
                                                         alt="Category"
                                                         style="width: 100%; height: 80px; object-fit: cover; border-radius: 0.375rem; margin-bottom: 0.5rem;">
                                                @else
                                                    <div style="width: 100%; height: 80px; background: #e5e7eb; border-radius: 0.375rem; margin-bottom: 0.5rem;"></div>
                                                @endif
                                                <p style="font-size: 0.75rem; font-weight: 600; color: #374151;">
                                                    {{ $category['title'] ?? 'Category' }}
                                                </p>
                                            </div>
                                        @endforeach
                                    @else
                                        <p style="color: #9ca3af; font-size: 0.875rem;">No categories added</p>
                                    @endif
                                </div>
                            </div>

                        @endif
                    @endforeach
                </div>
            @else
                <div style="text-align: center; padding: 3rem 1rem; color: #9ca3af;">
                    <x-heroicon-o-cube-transparent style="width: 4rem; height: 4rem; margin: 0 auto 0.75rem; color: #d1d5db;" />
                    <p style="font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem;">No content yet</p>
                    <p style="font-size: 0.875rem;">Start building your home layout by adding blocks</p>
                </div>
            @endif
        </div>

        <div style="margin-top: 1rem; padding: 0.75rem; background: #eff6ff; border-radius: 0.375rem; border-left: 4px solid #3b82f6;">
            <p style="font-size: 0.75rem; color: #1e40af;">
                <strong>Tip:</strong> Changes are reflected in real-time. The preview updates automatically as you edit.
            </p>
        </div>
    </x-filament::section>
</div>
