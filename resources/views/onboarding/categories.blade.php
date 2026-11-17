<!DOCTYPE html>
<html lang="en" style="height: 100%;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your categories — YottoCode</title>
    <link rel="icon" type="image/png" href="{{ asset('img/yottocode-favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --yc-navy: #0f1626; --yc-navy-hover: #1e2a44; --yc-blue: #2aabee; --yc-sky: #b3e6ff; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex flex-col" style="min-height: 100vh; margin: 0;">
    <header class="bg-white shadow-sm py-4">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <img src="{{ asset('img/yottocode-logo.svg') }}" alt="YottoCode" class="h-8 mx-auto">
        </div>
    </header>

    <main class="flex-1 px-4 sm:px-6 lg:px-8 py-10">
        <div class="max-w-3xl mx-auto">

            {{-- Step indicator (step 3 active) --}}
            <div class="flex items-center justify-center gap-2 mb-8 text-xs font-medium text-gray-500">
                @php $steps = ['Connect store', 'Branding', 'Categories', 'Store info', 'Finish']; $current = 2; @endphp
                @foreach ($steps as $i => $label)
                    <div class="flex items-center gap-2">
                        <span class="flex items-center justify-center h-6 w-6 rounded-full text-[11px] font-semibold
                            {{ $i <= $current ? 'text-white' : 'bg-gray-200 text-gray-500' }}"
                            @if($i <= $current) style="background-color: {{ $i < $current ? '#10b981' : 'var(--yc-blue)' }};" @endif>
                            @if($i < $current)
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>
                        <span class="{{ $i === $current ? 'text-gray-900' : '' }} hidden sm:inline">{{ $label }}</span>
                    </div>
                    @if (! $loop->last)
                        <span class="w-6 h-px bg-gray-200"></span>
                    @endif
                @endforeach
            </div>

            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Choose your main categories</h1>
                <p class="text-gray-500 mt-1">Pick the collections to show as the top categories in your app.</p>
            </div>

            @if ($loadError)
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $loadError }}
                </div>
            @endif

            @error('selected_ids')
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
            @enderror

            <form method="POST" action="{{ route('onboarding.categories.save', $tenant) }}">
                @csrf

                <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
                    @if (empty($collections))
                        <div class="text-center py-10">
                            <p class="text-gray-600 font-medium">No collections found in your store.</p>
                            <p class="text-sm text-gray-400 mt-1">Create collections in Shopify, then come back to this step.</p>
                        </div>
                    @else
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm font-medium text-gray-700">{{ count($collections) }} collections</p>
                            <p class="text-xs text-gray-400">Selected become your app's category tabs</p>
                        </div>

                        <div class="divide-y divide-gray-100 max-h-[28rem] overflow-y-auto -mx-2 px-2">
                            @foreach ($collections as $collection)
                                <label class="flex items-center gap-3 py-3 cursor-pointer group">
                                    <input type="checkbox" name="selected_ids[]" value="{{ $collection['id'] }}"
                                        @checked(in_array($collection['id'], $selectedIds, true))
                                        class="h-4 w-4 rounded border-gray-300 shrink-0"
                                        style="accent-color: var(--yc-blue);">
                                    <span class="h-10 w-10 rounded-lg bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                                        @if ($collection['image_url'])
                                            <img src="{{ $collection['image_url'] }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            <svg class="h-5 w-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                        @endif
                                    </span>
                                    <span class="text-sm text-gray-800 group-hover:text-gray-900">{{ $collection['title'] }}</span>
                                </label>
                            @endforeach
                        </div>

                        <p class="mt-4 text-xs text-gray-400">
                            We'll build your app navigation (<code>flutter-menu</code>) from these in Shopify. You can change it later in App Settings.
                        </p>
                    @endif
                </div>

                <div class="mt-6 flex items-center justify-between gap-4">
                    <a href="{{ \App\Http\Controllers\Onboarding\BrandingController::signedShowUrl($tenant) }}"
                        class="text-sm font-medium text-gray-500 hover:text-gray-700 transition">← Back</a>
                    @if (! empty($collections))
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 text-white py-3 px-8 rounded-xl transition font-semibold shadow-sm"
                            style="background-color: var(--yc-navy);"
                            onmouseover="this.style.backgroundColor='var(--yc-navy-hover)'"
                            onmouseout="this.style.backgroundColor='var(--yc-navy)'">
                            Save &amp; continue
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </main>

    <footer class="bg-gray-900 text-white py-4">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-300">
            &copy; {{ date('Y') }} Powered by <a href="https://yottocode.com" target="_blank" class="font-medium" style="color: var(--yc-blue);">YottoCode</a>
        </div>
    </footer>
</body>
</html>
