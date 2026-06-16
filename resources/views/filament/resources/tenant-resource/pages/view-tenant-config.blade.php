<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div>
                <h2 class="text-lg font-bold text-gray-950 dark:text-white">{{ $tenant->name }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $tenant->shopify_shop_domain }}</p>
            </div>
            @if ($appConfig)
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium
                    {{ $appConfig->publish_status === 'building' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                    {{ $appConfig->publish_status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' : '' }}
                    {{ $appConfig->publish_status === 'live' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                    {{ $appConfig->publish_status === 'submitted' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                    {{ $appConfig->publish_status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                ">
                    {{ ucfirst($appConfig->publish_status) }}
                </span>
            @endif
        </div>

        {{-- Editable Form (Branding + Theme & Build) --}}
        {{ $this->content(\Filament\Schemas\Schema::make($this)->key('content')) }}

        {{-- Imported Store Data (separate read-only table below) --}}
        @if ($onboardingSession && $onboardingSession->imported_data)
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
                <div class="px-6 py-4">
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">Imported Store Data</h3>
                </div>
                <div class="px-6 pb-6 space-y-5">
                    {{-- App Icon --}}
                    @if ($appConfig && $appConfig->app_icon_url)
                        <div>
                            <p class="text-sm font-medium text-gray-950 dark:text-white mb-2">App Icon</p>
                            <img src="{{ $appConfig->app_icon_url }}" alt="App Icon" style="width: 56px; height: 56px; object-fit: cover; border: 1px solid rgba(255,255,255,0.15);" class="rounded-xl bg-white">
                        </div>
                    @endif

                    {{-- Store Name --}}
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white mb-1.5">Store Name</p>
                        <div style="border: 1px solid rgba(255,255,255,0.15);" class="rounded-lg px-3 py-2 text-sm text-gray-950 dark:text-white">
                            {{ $onboardingSession->imported_data['store_info']['name'] ?? '—' }}
                        </div>
                    </div>

                    {{-- Domain --}}
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white mb-1.5">Domain</p>
                        <div style="border: 1px solid rgba(255,255,255,0.15);" class="rounded-lg px-3 py-2 text-sm text-gray-950 dark:text-white">
                            {{ $onboardingSession->shopify_domain }}
                        </div>
                    </div>

                    {{-- Currency --}}
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white mb-1.5">Currency</p>
                        <div style="border: 1px solid rgba(255,255,255,0.15);" class="rounded-lg px-3 py-2 text-sm text-gray-950 dark:text-white">
                            {{ $onboardingSession->imported_data['store_info']['currency'] ?? '—' }}
                        </div>
                    </div>

                    {{-- Country --}}
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white mb-1.5">Country</p>
                        <div style="border: 1px solid rgba(255,255,255,0.15);" class="rounded-lg px-3 py-2 text-sm text-gray-950 dark:text-white">
                            {{ $onboardingSession->imported_data['store_info']['country'] ?? '—' }}
                        </div>
                    </div>

                    {{-- Products --}}
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white mb-1.5">Products</p>
                        <div style="border: 1px solid rgba(255,255,255,0.15);" class="rounded-lg px-3 py-2 text-sm text-gray-950 dark:text-white">
                            {{ number_format($onboardingSession->imported_data['catalog']['product_count'] ?? 0) }}
                        </div>
                    </div>

                    {{-- Collections --}}
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white mb-1.5">Collections</p>
                        <div style="border: 1px solid rgba(255,255,255,0.15);" class="rounded-lg px-3 py-2 text-sm text-gray-950 dark:text-white">
                            {{ number_format($onboardingSession->imported_data['catalog']['category_count'] ?? 0) }}
                        </div>
                    </div>

                    {{-- Imported At --}}
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white mb-1.5">Imported At</p>
                        <div style="border: 1px solid rgba(255,255,255,0.15);" class="rounded-lg px-3 py-2 text-sm text-gray-950 dark:text-white">
                            @if (!empty($onboardingSession->imported_data['imported_at']))
                                {{ \Carbon\Carbon::parse($onboardingSession->imported_data['imported_at'])->format('M d, Y H:i') }}
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    {{-- Onboarding Completed --}}
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white mb-1.5">Onboarding Completed</p>
                        <div style="border: 1px solid rgba(255,255,255,0.15);" class="rounded-lg px-3 py-2 text-sm text-gray-950 dark:text-white">
                            {{ $onboardingSession->completed_at?->format('M d, Y H:i') ?? 'In progress' }}
                        </div>
                    </div>

                    {{-- Categories --}}
                    @php
                        $categories = \App\Models\Category::where('tenant_id', $tenant->id)->orderBy('sort_order')->get();
                    @endphp
                    @if ($categories->count() > 0)
                        <div>
                            <p class="text-sm font-medium text-gray-950 dark:text-white mb-3">Categories ({{ $categories->count() }})</p>
                            <div style="border: 1px solid rgba(255,255,255,0.15);" class="rounded-lg overflow-hidden divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($categories as $category)
                                    <div class="flex items-center gap-3 px-4 py-2.5">
                                        @if ($category->image_url)
                                            <img src="{{ $category->image_url }}" alt="" style="width: 32px; height: 32px; object-fit: cover;" class="rounded-md flex-shrink-0">
                                        @else
                                            <div style="width: 32px; height: 32px;" class="rounded-md bg-gray-100 dark:bg-gray-800 flex-shrink-0 flex items-center justify-center">
                                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                            </div>
                                        @endif
                                        <span class="text-sm text-gray-950 dark:text-white">{{ $category->title }}</span>
                                        <span class="ml-auto text-xs text-gray-400">{{ $category->handle }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
