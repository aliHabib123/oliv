<!DOCTYPE html>
<html lang="en" style="height: 100%;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your branding — YottoCode</title>
    @include('partials.yottocode-head')
    <script src="https://cdn.tailwindcss.com"></script>
    </head>
<body class="bg-gray-50 flex flex-col" style="min-height: 100vh; margin: 0;">
    <header class="bg-white shadow-sm py-4">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <img src="{{ asset('img/yottocode-logo.svg') }}" alt="YottoCode" class="h-8 mx-auto">
        </div>
    </header>

    <main class="flex-1 px-4 sm:px-6 lg:px-8 py-10">
        <div class="max-w-4xl mx-auto">

            {{-- Step indicator (step 2 active) --}}
            <div class="flex items-center justify-center gap-2 mb-8 text-xs font-medium text-gray-500">
                @php $steps = ['Connect store', 'Branding', 'Categories', 'Store info', 'Finish']; $current = 1; @endphp
                @foreach ($steps as $i => $label)
                    <div class="flex items-center gap-2">
                        <span class="flex items-center justify-center h-6 w-6 rounded-full text-[11px] font-semibold
                            {{ $i < $current ? 'text-white' : ($i === $current ? 'text-white' : 'bg-gray-200 text-gray-500') }}"
                            @if($i <= $current) style="background-color: {{ $i < $current ? '#10b981' : 'var(--yc-blue-strong)' }};" @endif>
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
                <h1 class="text-2xl font-bold text-gray-900">Make it yours</h1>
                <p class="text-gray-500 mt-1">We pulled these from your store — tweak anything you like.</p>
            </div>

            <form method="POST" action="{{ route('onboarding.branding.save', $tenant) }}" enctype="multipart/form-data">
                @csrf
                @if ($suggestedLogoUrl)
                    <input type="hidden" name="suggested_logo_url" value="{{ $suggestedLogoUrl }}">
                @endif

                <div class="grid md:grid-cols-2 gap-6">
                    {{-- Form card --}}
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        {{-- App name --}}
                        <label for="app_name" class="block text-sm font-medium text-gray-700 mb-2">App name</label>
                        <input type="text" name="app_name" id="app_name"
                            value="{{ old('app_name', $appName) }}"
                            oninput="document.getElementById('pv-name').textContent = this.value || 'Your App'"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 @error('app_name') ring-1 ring-red-400 @enderror"
                            style="--tw-ring-color: var(--yc-blue-strong);" required>
                        @error('app_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                        {{-- Logo --}}
                        <label class="block text-sm font-medium text-gray-700 mt-6 mb-2">Logo</label>
                        <div class="flex items-center gap-4">
                            <div class="h-16 w-16 rounded-xl border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden shrink-0">
                                @php
                                    $initialLogo = $logoPath ? asset('storage/'.$logoPath) : $suggestedLogoUrl;
                                @endphp
                                <img id="pv-logo-thumb" src="{{ $initialLogo }}" alt=""
                                    class="h-full w-full object-contain {{ $initialLogo ? '' : 'hidden' }}">
                                <span id="pv-logo-empty" class="text-gray-300 text-xs {{ $initialLogo ? 'hidden' : '' }}">No logo</span>
                            </div>
                            <label class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                Upload logo
                                <input type="file" name="logo" accept="image/*" class="hidden" onchange="previewLogo(this)">
                            </label>
                        </div>
                        @if ($suggestedLogoUrl)
                            <p class="mt-2 text-xs text-gray-400">Suggested from your Shopify brand assets. Upload to replace.</p>
                        @endif
                        @error('logo') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                        {{-- Colors --}}
                        <div class="grid grid-cols-2 gap-4 mt-6">
                            <div>
                                <label for="primary_color" class="block text-sm font-medium text-gray-700 mb-2">Primary color</label>
                                <div class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2">
                                    <input type="color" id="primary_color" name="primary_color"
                                        value="{{ old('primary_color', $primaryColor) }}"
                                        oninput="syncColor('primary', this.value)"
                                        class="h-8 w-8 rounded cursor-pointer border-0 bg-transparent p-0">
                                    <span id="primary_hex" class="text-sm text-gray-600 font-mono">{{ old('primary_color', $primaryColor) }}</span>
                                </div>
                                @error('primary_color') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="secondary_color" class="block text-sm font-medium text-gray-700 mb-2">Secondary color</label>
                                <div class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2">
                                    <input type="color" id="secondary_color" name="secondary_color"
                                        value="{{ old('secondary_color', $secondaryColor) }}"
                                        oninput="syncColor('secondary', this.value)"
                                        class="h-8 w-8 rounded cursor-pointer border-0 bg-transparent p-0">
                                    <span id="secondary_hex" class="text-sm text-gray-600 font-mono">{{ old('secondary_color', $secondaryColor) }}</span>
                                </div>
                                @error('secondary_color') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Live preview --}}
                    <div class="bg-white rounded-2xl shadow-lg p-8 flex flex-col items-center justify-center">
                        <p class="text-xs uppercase tracking-wide text-gray-400 mb-4">Live preview</p>
                        <div class="w-56 rounded-[2rem] border-8 border-gray-900 overflow-hidden shadow-xl bg-white">
                            <div id="pv-header" class="px-4 pt-6 pb-5 text-center" style="background-color: {{ $primaryColor }};">
                                <div class="mx-auto h-12 w-12 rounded-xl bg-white/95 flex items-center justify-center overflow-hidden mb-2">
                                    <img id="pv-logo-app" src="{{ $initialLogo }}" alt=""
                                        class="h-full w-full object-contain {{ $initialLogo ? '' : 'hidden' }}">
                                    <span id="pv-logo-app-empty" class="text-gray-300 text-[10px] {{ $initialLogo ? 'hidden' : '' }}">Logo</span>
                                </div>
                                <p id="pv-name" class="text-white font-semibold text-sm truncate">{{ $appName ?: 'Your App' }}</p>
                            </div>
                            <div class="p-3 space-y-2">
                                <div class="h-16 rounded-lg bg-gray-100"></div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="h-14 rounded-lg bg-gray-100"></div>
                                    <div class="h-14 rounded-lg bg-gray-100"></div>
                                </div>
                                <button type="button" id="pv-button" class="w-full rounded-lg py-2 text-white text-xs font-semibold" style="background-color: {{ $secondaryColor }};">
                                    Shop now
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between gap-4">
                    <a href="{{ \App\Http\Controllers\Onboarding\ShopifyOnboardingController::signedShowUrl($tenant) }}"
                        class="text-sm font-medium text-gray-500 hover:text-gray-700 transition">← Back</a>
                    <button type="submit"
                        class="yc-btn inline-flex items-center justify-center gap-2 py-3 px-8 transition font-semibold">
                        Save &amp; continue
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="bg-gray-900 text-white py-4">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-300">
            &copy; {{ date('Y') }} Powered by <a href="https://yottocode.com" target="_blank" class="font-medium" style="color: var(--yc-blue-strong);">YottoCode</a>
        </div>
    </footer>

    <script>
        function syncColor(which, value) {
            document.getElementById(which + '_hex').textContent = value;
            if (which === 'primary') {
                document.getElementById('pv-header').style.backgroundColor = value;
            } else {
                document.getElementById('pv-button').style.backgroundColor = value;
            }
        }

        function previewLogo(input) {
            if (!input.files || !input.files[0]) return;
            const url = URL.createObjectURL(input.files[0]);
            ['pv-logo-thumb', 'pv-logo-app'].forEach(id => {
                const img = document.getElementById(id);
                img.src = url;
                img.classList.remove('hidden');
            });
            ['pv-logo-empty', 'pv-logo-app-empty'].forEach(id => document.getElementById(id)?.classList.add('hidden'));
        }
    </script>
</body>
</html>
