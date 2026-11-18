<!DOCTYPE html>
<html lang="en" style="height: 100%;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store info — YottoCode</title>
    @include('partials.yottocode-head')
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .fld { width: 100%; border-radius: 0.5rem; border: 1px solid #d1d5db; padding: 0.625rem 0.875rem; font-size: 0.875rem; }
        .fld:focus { outline: none; box-shadow: 0 0 0 2px var(--yc-blue-strong); }
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

            {{-- Step indicator (step 4 active) --}}
            <div class="flex items-center justify-center gap-2 mb-8 text-xs font-medium text-gray-500">
                @php $steps = ['Connect store', 'Branding', 'Categories', 'Store info', 'Finish']; $current = 3; @endphp
                @foreach ($steps as $i => $label)
                    <div class="flex items-center gap-2">
                        <span class="flex items-center justify-center h-6 w-6 rounded-full text-[11px] font-semibold
                            {{ $i <= $current ? 'text-white' : 'bg-gray-200 text-gray-500' }}"
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
                <h1 class="text-2xl font-bold text-gray-900">Add your store info</h1>
                <p class="text-gray-500 mt-1">Contact details, social links and policies. All optional — you can add these later.</p>
            </div>

            <form method="POST" action="{{ route('onboarding.store-info.save', $tenant) }}" id="storeInfoForm">
                @csrf

                {{-- Contact --}}
                <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 mb-5">
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">Contact</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                            <input type="email" name="contact[email]" value="{{ old('contact.email', $contact['email'] ?? '') }}" placeholder="info@example.com" class="fld @error('contact.email') ring-1 ring-red-400 @enderror">
                            @error('contact.email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                            <input type="tel" name="contact[phone]" value="{{ old('contact.phone', $contact['phone'] ?? '') }}" placeholder="+971500000000" class="fld">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Address</label>
                            <textarea name="contact[address]" rows="2" placeholder="123 Fashion St, City, Country" class="fld">{{ old('contact.address', $contact['address'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Social --}}
                <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 mb-5">
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">Social media</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        @foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'twitter' => 'Twitter / X', 'tiktok' => 'TikTok'] as $key => $label)
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ $label }} URL</label>
                                <input type="url" name="social_media[{{ $key }}]" value="{{ old('social_media.'.$key, $social[$key] ?? '') }}" placeholder="https://{{ $key }}.com/yourpage" class="fld @error('social_media.'.$key) ring-1 ring-red-400 @enderror">
                                @error('social_media.'.$key) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Policies --}}
                <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">Policies (URLs)</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        @foreach (['privacy_policy' => 'Privacy Policy', 'return_policy' => 'Return Policy', 'shipping_policy' => 'Shipping Policy', 'about_us' => 'About Us'] as $key => $label)
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
                                <input type="url" name="static_pages[{{ $key }}]" value="{{ old('static_pages.'.$key, $pages[$key] ?? '') }}" placeholder="https://yoursite.com/{{ str_replace('_', '-', $key) }}" class="fld @error('static_pages.'.$key) ring-1 ring-red-400 @enderror">
                                @error('static_pages.'.$key) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between gap-4">
                    <a href="{{ \App\Http\Controllers\Onboarding\CategoriesController::signedShowUrl($tenant) }}"
                        class="text-sm font-medium text-gray-500 hover:text-gray-700 transition">← Back</a>
                    <div class="flex items-center gap-4">
                        <button type="submit" class="text-sm font-medium text-gray-500 hover:text-gray-700 transition" onclick="this.form.querySelectorAll('input,textarea').forEach(el => { if (el.name !== '_token') el.value=''; })">
                            Skip for now
                        </button>
                        <button type="submit"
                            class="yc-btn inline-flex items-center justify-center gap-2 py-3 px-8 transition font-semibold">
                            Save &amp; finish
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <footer class="bg-gray-900 text-white py-4">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-300">
            &copy; {{ date('Y') }} Powered by <a href="https://yottocode.com" target="_blank" class="font-medium" style="color: var(--yc-blue-strong);">YottoCode</a>
        </div>
    </footer>
</body>
</html>
