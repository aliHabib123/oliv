<x-filament-panels::page>
    @if(!$this->flutterMenuExists)
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 2rem; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: start; gap: 1rem;">
                <div style="flex-shrink: 0;">
                    <svg style="width: 2rem; height: 2rem; color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div style="flex: 1;">
                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #991b1b; margin: 0 0 0.5rem 0;">Flutter Menu Not Configured</h3>
                    <p style="color: #7f1d1d; margin-bottom: 1rem; line-height: 1.5;">
                        The <strong>"flutter-menu"</strong> navigation menu is not configured in your Shopify store.
                        This menu is required for category-based home layouts.
                    </p>
                    <div style="background: white; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem;">
                        <h4 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0 0 0.5rem 0;">Setup Instructions:</h4>
                        <ol style="color: #374151; font-size: 0.875rem; line-height: 1.6; margin: 0; padding-left: 1.25rem;">
                            <li style="margin-bottom: 0.5rem;">Go to your <strong>Shopify Admin</strong></li>
                            <li style="margin-bottom: 0.5rem;">Navigate to <strong>Online Store → Navigation</strong></li>
                            <li style="margin-bottom: 0.5rem;">Click <strong>"Add menu"</strong></li>
                            <li style="margin-bottom: 0.5rem;">Set the title to <strong>"flutter-menu"</strong> (exact name required)</li>
                            <li style="margin-bottom: 0.5rem;">Add your main category collections as menu items</li>
                            <li>Click <strong>"Save menu"</strong></li>
                        </ol>
                    </div>
                    <p style="color: #7f1d1d; font-size: 0.875rem; margin: 0;">
                        After creating the menu, return here and sync your categories from App Settings.
                    </p>
                </div>
            </div>
        </div>

        <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 2rem; text-align: center;">
            <a href="{{ route('filament.admin.pages.app-settings') }}"
               style="display: inline-block; padding: 0.625rem 1.25rem; background: #3b82f6; color: white; font-weight: 500; border-radius: 0.5rem; text-decoration: none;">
                Go to App Settings to Sync
            </a>
        </div>
    @elseif(!$this->flutterMenuHasItems)
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 2rem; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: start; gap: 1rem;">
                <div style="flex-shrink: 0;">
                    <svg style="width: 2rem; height: 2rem; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div style="flex: 1;">
                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #92400e; margin: 0 0 0.5rem 0;">Flutter Menu is Empty</h3>
                    <p style="color: #78350f; margin-bottom: 1rem; line-height: 1.5;">
                        The <strong>"flutter-menu"</strong> exists but has no menu items.
                        You need to add your main category collections to this menu.
                    </p>
                    <div style="background: white; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem;">
                        <h4 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0 0 0.5rem 0;">To Add Categories:</h4>
                        <ol style="color: #374151; font-size: 0.875rem; line-height: 1.6; margin: 0; padding-left: 1.25rem;">
                            <li style="margin-bottom: 0.5rem;">Go to <strong>Shopify Admin → Online Store → Navigation</strong></li>
                            <li style="margin-bottom: 0.5rem;">Find and click on <strong>"flutter-menu"</strong></li>
                            <li style="margin-bottom: 0.5rem;">Click <strong>"Add menu item"</strong></li>
                            <li style="margin-bottom: 0.5rem;">Select your category collections (e.g., Men, Women, Kids)</li>
                            <li>Click <strong>"Save menu"</strong></li>
                        </ol>
                    </div>
                    <p style="color: #78350f; font-size: 0.875rem; margin: 0;">
                        After adding items, return here and sync your categories from App Settings.
                    </p>
                </div>
            </div>
        </div>

        <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 2rem; text-align: center;">
            <a href="{{ route('filament.admin.pages.app-settings') }}"
               style="display: inline-block; padding: 0.625rem 1.25rem; background: #3b82f6; color: white; font-weight: 500; border-radius: 0.5rem; text-decoration: none;">
                Go to App Settings to Sync
            </a>
        </div>
    @elseif(empty($this->categories))
        <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 3rem; text-align: center;">
            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">No Categories Synced</h3>
            <p style="color: #6b7280; margin-bottom: 1.5rem;">Sync your main categories from Shopify first to assign layouts.</p>
            <a href="{{ route('filament.admin.pages.app-settings') }}"
               style="display: inline-block; padding: 0.625rem 1.25rem; background: #3b82f6; color: white; font-weight: 500; border-radius: 0.5rem; text-decoration: none;">
                Go to App Settings
            </a>
        </div>
    @else
        <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <th style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                            Category
                        </th>
                        <th style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                            Status
                        </th>
                        <th style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                            Assigned Layout
                        </th>
                        <th style="padding: 0.75rem 1.5rem; text-align: center; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                            Preview
                        </th>
                        <th style="padding: 0.75rem 1.5rem; text-align: right; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->assignments as $handle => $assignment)
                        <tr style="border-bottom: 1px solid #e5e7eb;" onmouseover="this.style.backgroundColor='#f9fafb'" onmouseout="this.style.backgroundColor='white'">
                            <td style="padding: 1rem 1.5rem;">
                                <div style="font-weight: 500; font-size: 0.875rem; color: #111827;">
                                    {{ $assignment['category_title'] }}
                                </div>
                                <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                                    {{ $handle }}
                                </div>
                            </td>

                            <td style="padding: 1rem 1.5rem;">
                                @if($assignment['has_assignment'])
                                    @if($assignment['is_active'])
                                        <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; background: #d1fae5; color: #065f46; font-size: 0.75rem; font-weight: 500; border-radius: 9999px;">
                                            Active
                                        </span>
                                    @else
                                        <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; background: #f3f4f6; color: #374151; font-size: 0.75rem; font-weight: 500; border-radius: 9999px;">
                                            Inactive
                                        </span>
                                    @endif
                                @else
                                    <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; background: #fef3c7; color: #92400e; font-size: 0.75rem; font-weight: 500; border-radius: 9999px;">
                                        Not Assigned
                                    </span>
                                @endif
                            </td>

                            <td style="padding: 1rem 1.5rem; font-size: 0.875rem; color: #111827;">
                                {{ $assignment['layout_name'] ?? '—' }}
                            </td>

                            <td style="padding: 1rem 1.5rem; text-align: center;">
                                @if($assignment['has_assignment'] && $assignment['layout_data'])
                                    <div x-data="{ showPreview: false }" style="display: inline-block;">
                                        <button
                                            type="button"
                                            @click="showPreview = true"
                                            style="padding: 0.375rem 0.5rem; background: #6b7280; color: white; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem; border: none; cursor: pointer; transition: all 0.15s; display: inline-flex; align-items: center; gap: 0.25rem;"
                                            onmouseover="this.style.backgroundColor='#4b5563'"
                                            onmouseout="this.style.backgroundColor='#6b7280'">
                                            <svg style="width: 0.875rem; height: 0.875rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </button>

                                        <!-- Modal Popup -->
                                        <div x-show="showPreview"
                                             x-cloak
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0"
                                             x-transition:enter-end="opacity-100"
                                             x-transition:leave="transition ease-in duration-150"
                                             x-transition:leave-start="opacity-100"
                                             x-transition:leave-end="opacity-0"
                                             @click="showPreview = false"
                                             @keydown.escape.window="showPreview = false"
                                             style="position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; z-index: 9999; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; padding: 1rem;">
                                            <div @click.stop
                                                 style="background: white; border-radius: 0.75rem; width: 90%; max-width: 56rem; max-height: 90vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); margin-left: auto; margin-right: auto;">
                                                <!-- Modal Header -->
                                                <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: #f9fafb;">
                                                    <div>
                                                        <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0;">Layout Preview</h3>
                                                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0 0;">{{ $assignment['category_title'] }} - {{ $assignment['layout_name'] }}</p>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        @click="showPreview = false"
                                                        style="padding: 0.5rem; background: transparent; border: none; color: #6b7280; cursor: pointer; border-radius: 0.375rem; transition: all 0.15s;"
                                                        onmouseover="this.style.backgroundColor='#f3f4f6'"
                                                        onmouseout="this.style.backgroundColor='transparent'">
                                                        <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>

                                                <!-- Modal Body -->
                                                <div style="padding: 1.5rem; overflow-y: auto; max-height: calc(90vh - 8rem); background: #f3f4f6;">
                                                    <style>
                                                        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');
                                                        .preview-scroll-{{ $handle }}::-webkit-scrollbar { width: 4px; }
                                                        .preview-scroll-{{ $handle }}::-webkit-scrollbar-track { background: transparent; }
                                                        .preview-scroll-{{ $handle }}::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.2); border-radius: 2px; }
                                                    </style>

                                                    <div style="display: flex; justify-content: center; align-items: center;">
                                                        {{-- iPhone Mockup Container --}}
                                                        <div style="position: relative; width: 375px; height: 600px; flex-shrink: 0;">
                                                            {{-- iPhone Frame Image --}}
                                                            <img src="{{ asset('img/apple-iphone-13-pro-max-2021-medium.png') }}"
                                                                 alt="iPhone Frame"
                                                                 style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 2; pointer-events: none;">

                                                            {{-- Screen Content Area --}}
                                                            <div style="position: absolute; top: 15px; left: 16px; right: 16px; bottom: 15px; background: white; border-radius: 40px; overflow: hidden; z-index: 1;">
                                                                <div class="preview-scroll-{{ $handle }}" style="height: 100%; overflow-y: auto; overflow-x: hidden; background: #f9fafb; -webkit-overflow-scrolling: touch;">
                                                                    @if(!empty($assignment['layout_data']))
                                                                        <div style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem;">
                                                                            @foreach($assignment['layout_data'] as $block)
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
                                                    </div>
                                                </div>

                                                <!-- Modal Footer -->
                                                <div style="padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; background: white;">
                                                    <button
                                                        type="button"
                                                        @click="showPreview = false"
                                                        style="padding: 0.5rem 1rem; background: #6b7280; color: white; font-size: 0.875rem; font-weight: 500; border-radius: 0.375rem; border: none; cursor: pointer; transition: all 0.15s;"
                                                        onmouseover="this.style.backgroundColor='#4b5563'"
                                                        onmouseout="this.style.backgroundColor='#6b7280'">
                                                        Close
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span style="color: #9ca3af; font-size: 0.875rem;">—</span>
                                @endif
                            </td>

                            <td style="padding: 1rem 1.5rem;">
                                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; align-items: center;">
                                    @if($assignment['has_assignment'] && $assignment['layout_data'])
                                        <button
                                            type="button"
                                            wire:click="toggleStatus('{{ $handle }}')"
                                            style="padding: 0.375rem 0.75rem; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem; border: none; cursor: pointer; transition: all 0.15s; {{ $assignment['is_active'] ? 'background: #f3f4f6; color: #374151;' : 'background: #d1fae5; color: #065f46;' }}"
                                            onmouseover="this.style.opacity='0.8'"
                                            onmouseout="this.style.opacity='1'">
                                            {{ $assignment['is_active'] ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    @endif

                                    @if(!$assignment['layout_data'])
                                        <div x-data="{ showAssignModal: false }" style="position: relative; display: inline-block;">
                                            <button
                                                type="button"
                                                @click="showAssignModal = true"
                                                style="padding: 0.375rem 0.75rem; background: #3b82f6; color: white; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem; border: none; cursor: pointer; transition: all 0.15s;"
                                                onmouseover="this.style.backgroundColor='#2563eb'"
                                                onmouseout="this.style.backgroundColor='#3b82f6'">
                                                Assign Layout
                                            </button>

                                            <!-- Assignment Modal -->
                                            <div x-show="showAssignModal"
                                                 x-cloak
                                                 @click="showAssignModal = false"
                                                 @keydown.escape.window="showAssignModal = false"
                                                 style="position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; z-index: 9999; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; padding: 1rem;">
                                                <div @click.stop
                                                     style="background: white; border-radius: 0.75rem; width: 95%; max-width: 72rem; max-height: 90vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">

                                                    <!-- Assignment Modal Header -->
                                                    <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: #f9fafb;">
                                                        <div>
                                                            <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0;">Assign Layout</h3>
                                                            <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0 0;">Choose a template or copy from another category for {{ $assignment['category_title'] }}</p>
                                                        </div>
                                                        <button
                                                            type="button"
                                                            @click="showAssignModal = false"
                                                            style="padding: 0.5rem; background: transparent; border: none; color: #6b7280; cursor: pointer; border-radius: 0.375rem; transition: all 0.15s;"
                                                            onmouseover="this.style.backgroundColor='#f3f4f6'"
                                                            onmouseout="this.style.backgroundColor='transparent'">
                                                            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </div>

                                                    <!-- Assignment Modal Body -->
                                                    <div style="padding: 1.5rem; overflow-y: auto; max-height: calc(90vh - 10rem); background: #f9fafb;">
                                                        @include('filament.pages.partials.assignment-modal-content', ['handle' => $handle, 'categoryTitle' => $assignment['category_title']])
                                                    </div>

                                                    <!-- Assignment Modal Footer -->
                                                    <div style="padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; background: white;">
                                                        <button
                                                            type="button"
                                                            @click="showAssignModal = false"
                                                            style="padding: 0.5rem 1rem; background: #6b7280; color: white; font-size: 0.875rem; font-weight: 500; border-radius: 0.375rem; border: none; cursor: pointer; transition: all 0.15s;"
                                                            onmouseover="this.style.backgroundColor='#4b5563'"
                                                            onmouseout="this.style.backgroundColor='#6b7280'">
                                                            Close
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
