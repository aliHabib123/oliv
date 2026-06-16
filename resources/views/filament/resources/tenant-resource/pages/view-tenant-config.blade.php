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

        {{-- Imported Store Data (read-only, styled like Branding form) --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5">
                <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">Imported Store Data</h3>
            </div>
            <div class="p-6 space-y-6">
                @if ($onboardingSession && $onboardingSession->imported_data)
                    {{-- App Icon --}}
                    @if ($appConfig && $appConfig->app_icon_url)
                        <div>
                            <p class="fi-fo-field-wrp-label text-sm font-medium leading-6 text-gray-950 dark:text-white">App Icon</p>
                            <div class="mt-1">
                                <img src="{{ $appConfig->app_icon_url }}" alt="App Icon" style="width: 48px; height: 48px; object-fit: cover;" class="rounded-xl">
                            </div>
                        </div>
                    @endif

                    {{-- Store Name --}}
                    <div>
                        <p class="fi-fo-field-wrp-label text-sm font-medium leading-6 text-gray-950 dark:text-white">Store Name</p>
                        <div class="mt-1">
                            <input type="text" readonly value="{{ $onboardingSession->imported_data['store_info']['name'] ?? '—' }}" class="fi-input block w-full rounded-lg border-none py-1.5 px-3 text-base text-gray-950 outline-none dark:text-white sm:text-sm sm:leading-6 bg-white dark:bg-white/5 shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20 cursor-default" />
                        </div>
                    </div>

                    {{-- Domain --}}
                    <div>
                        <p class="fi-fo-field-wrp-label text-sm font-medium leading-6 text-gray-950 dark:text-white">Domain</p>
                        <div class="mt-1">
                            <input type="text" readonly value="{{ $onboardingSession->shopify_domain }}" class="fi-input block w-full rounded-lg border-none py-1.5 px-3 text-base text-gray-950 outline-none dark:text-white sm:text-sm sm:leading-6 bg-white dark:bg-white/5 shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20 cursor-default" />
                        </div>
                    </div>

                    {{-- Currency --}}
                    <div>
                        <p class="fi-fo-field-wrp-label text-sm font-medium leading-6 text-gray-950 dark:text-white">Currency</p>
                        <div class="mt-1">
                            <input type="text" readonly value="{{ $onboardingSession->imported_data['store_info']['currency'] ?? '—' }}" class="fi-input block w-full rounded-lg border-none py-1.5 px-3 text-base text-gray-950 outline-none dark:text-white sm:text-sm sm:leading-6 bg-white dark:bg-white/5 shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20 cursor-default" />
                        </div>
                    </div>

                    {{-- Country --}}
                    <div>
                        <p class="fi-fo-field-wrp-label text-sm font-medium leading-6 text-gray-950 dark:text-white">Country</p>
                        <div class="mt-1">
                            <input type="text" readonly value="{{ $onboardingSession->imported_data['store_info']['country'] ?? '—' }}" class="fi-input block w-full rounded-lg border-none py-1.5 px-3 text-base text-gray-950 outline-none dark:text-white sm:text-sm sm:leading-6 bg-white dark:bg-white/5 shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20 cursor-default" />
                        </div>
                    </div>

                    {{-- Products --}}
                    <div>
                        <p class="fi-fo-field-wrp-label text-sm font-medium leading-6 text-gray-950 dark:text-white">Products</p>
                        <div class="mt-1">
                            <input type="text" readonly value="{{ number_format($onboardingSession->imported_data['catalog']['product_count'] ?? 0) }}" class="fi-input block w-full rounded-lg border-none py-1.5 px-3 text-base text-gray-950 outline-none dark:text-white sm:text-sm sm:leading-6 bg-white dark:bg-white/5 shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20 cursor-default" />
                        </div>
                    </div>

                    {{-- Collections --}}
                    <div>
                        <p class="fi-fo-field-wrp-label text-sm font-medium leading-6 text-gray-950 dark:text-white">Collections</p>
                        <div class="mt-1">
                            <input type="text" readonly value="{{ number_format($onboardingSession->imported_data['catalog']['category_count'] ?? 0) }}" class="fi-input block w-full rounded-lg border-none py-1.5 px-3 text-base text-gray-950 outline-none dark:text-white sm:text-sm sm:leading-6 bg-white dark:bg-white/5 shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20 cursor-default" />
                        </div>
                    </div>

                    {{-- Imported At --}}
                    <div>
                        <p class="fi-fo-field-wrp-label text-sm font-medium leading-6 text-gray-950 dark:text-white">Imported At</p>
                        <div class="mt-1">
                            <input type="text" readonly value="@if(!empty($onboardingSession->imported_data['imported_at'])){{ \Carbon\Carbon::parse($onboardingSession->imported_data['imported_at'])->format('M d, Y H:i') }}@else—@endif" class="fi-input block w-full rounded-lg border-none py-1.5 px-3 text-base text-gray-950 outline-none dark:text-white sm:text-sm sm:leading-6 bg-white dark:bg-white/5 shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20 cursor-default" />
                        </div>
                    </div>

                    {{-- Onboarding Completed --}}
                    <div>
                        <p class="fi-fo-field-wrp-label text-sm font-medium leading-6 text-gray-950 dark:text-white">Onboarding Completed</p>
                        <div class="mt-1">
                            <input type="text" readonly value="{{ $onboardingSession->completed_at?->format('M d, Y H:i') ?? 'In progress' }}" class="fi-input block w-full rounded-lg border-none py-1.5 px-3 text-base text-gray-950 outline-none dark:text-white sm:text-sm sm:leading-6 bg-white dark:bg-white/5 shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20 cursor-default" />
                        </div>
                    </div>

                    {{-- Categories --}}
                    @php
                        $categories = \App\Models\Category::where('tenant_id', $tenant->id)->orderBy('sort_order')->get();
                    @endphp
                    @if ($categories->count() > 0)
                        <div>
                            <p class="fi-fo-field-wrp-label text-sm font-medium leading-6 text-gray-950 dark:text-white">Categories ({{ $categories->count() }})</p>
                            <div class="mt-1 rounded-lg shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20 overflow-hidden divide-y divide-gray-950/5 dark:divide-white/10 bg-white dark:bg-white/5">
                                @foreach ($categories as $category)
                                    <div class="flex items-center gap-3 px-3 py-2">
                                        @if ($category->image_url)
                                            <img src="{{ $category->image_url }}" alt="" style="width: 28px; height: 28px; object-fit: cover;" class="rounded-md flex-shrink-0">
                                        @else
                                            <div style="width: 28px; height: 28px;" class="rounded-md bg-gray-100 dark:bg-gray-800 flex-shrink-0 flex items-center justify-center">
                                                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                            </div>
                                        @endif
                                        <span class="text-sm text-gray-950 dark:text-white">{{ $category->title }}</span>
                                        <span class="ml-auto text-xs text-gray-400">{{ $category->handle }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No imported data available for this tenant.</p>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
