<!DOCTYPE html>
<html lang="en" style="height: 100%;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect your store — YottoCode</title>
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

            {{-- Step indicator --}}
            <div class="flex items-center justify-center gap-2 mb-8 text-xs font-medium text-gray-500">
                @php $steps = ['Connect store', 'Branding', 'Categories', 'Store info', 'Finish']; @endphp
                @foreach ($steps as $i => $label)
                    <div class="flex items-center gap-2">
                        <span class="flex items-center justify-center h-6 w-6 rounded-full text-[11px] font-semibold
                            {{ $i === 0 ? 'text-white' : 'bg-gray-200 text-gray-500' }}"
                            @if($i === 0) style="background-color: var(--yc-blue);" @endif>
                            {{ $i + 1 }}
                        </span>
                        <span class="{{ $i === 0 ? 'text-gray-900' : '' }} hidden sm:inline">{{ $label }}</span>
                    </div>
                    @if (! $loop->last)
                        <span class="w-6 h-px bg-gray-200"></span>
                    @endif
                @endforeach
            </div>

            {{-- Flash / warning messages --}}
            @if (session('warning'))
                <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ session('warning') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                @if ($connected)
                    {{-- ───────────── Connected state ───────────── --}}
                    <div class="p-8 sm:p-10 text-center">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-5"
                            style="background-color: rgba(16, 185, 129, 0.1);">
                            <svg class="h-9 w-9 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">Your store is connected</h1>
                        <p class="text-gray-600 mb-6">
                            <span class="font-semibold text-gray-900">{{ $tenant->shopify_shop_domain }}</span>
                            is now linked to your app.
                        </p>

                        <div class="rounded-xl bg-gray-50 border border-gray-100 p-5 text-left mb-8">
                            <p class="text-sm font-semibold text-gray-900 mb-3">We've set up:</p>
                            <ul class="space-y-2 text-sm text-gray-600">
                                <li class="flex items-start gap-2">
                                    <svg class="h-5 w-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Secure admin access to your catalog and orders
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="h-5 w-5 {{ $tenant->shopify_storefront_token ? 'text-emerald-500' : 'text-amber-500' }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Storefront access for the mobile app {{ $tenant->shopify_storefront_token ? '' : '(pending — our team will finish this)' }}
                                </li>
                            </ul>
                        </div>

                        <a href="{{ \App\Http\Controllers\Onboarding\BrandingController::signedShowUrl($tenant) }}"
                            class="inline-flex w-full items-center justify-center gap-2 text-white py-3.5 px-6 rounded-xl transition font-semibold shadow-sm"
                            style="background-color: var(--yc-navy);"
                            onmouseover="this.style.backgroundColor='var(--yc-navy-hover)'"
                            onmouseout="this.style.backgroundColor='var(--yc-navy)'">
                            Continue to branding
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>

                        <form method="POST" action="{{ route('onboarding.shopify.connect', $tenant) }}" class="mt-4">
                            @csrf
                            <input type="hidden" name="shop_domain" value="">
                            <button type="button"
                                onclick="document.getElementById('reconnect').classList.toggle('hidden')"
                                class="text-sm text-gray-500 hover:text-gray-700 transition">
                                Connect a different store
                            </button>
                            <div id="reconnect" class="hidden mt-4 flex gap-2">
                                <input type="text" name="shop_domain" placeholder="your-store.myshopify.com"
                                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                    style="--tw-ring-color: var(--yc-blue);">
                                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background-color: var(--yc-navy);">Reconnect</button>
                            </div>
                        </form>
                    </div>
                @else
                    {{-- ───────────── Connect state ───────────── --}}
                    <div class="grid md:grid-cols-5">
                        {{-- Left: pitch --}}
                        <div class="md:col-span-2 p-8 text-white" style="background-color: var(--yc-navy);">
                            <div class="flex items-center gap-2 mb-6">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="#95BF47" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M15.3 4.3c-.1 0-.3 0-.4.1-.1 0-.5.1-1.1.3-.6-1.7-1.6-2.4-2.6-2.4h-.2c-.3-.4-.7-.6-1.2-.6-2 .1-3 2.6-3.3 3.9l-1.6.5c-.5.2-.5.2-.6.7C4.2 7.2 3 16.6 3 16.6l9.2 1.7 5-1.2S15.4 4.4 15.3 4.3zM12.5 3.4c-.4.1-.8.2-1.3.4 0-.7-.1-1.5-.4-2 .8.1 1.3 1 1.7 1.6zm-2.2-1.5c.3 0 .5.1.7.3-.6.3-1.2.9-1.5 2.1l-1.3.4c.4-1.2 1.2-2.7 2.1-2.8z"/>
                                </svg>
                                <span class="text-sm font-semibold tracking-wide uppercase opacity-80">Step 1</span>
                            </div>
                            <h2 class="text-2xl font-bold mb-3">Connect your Shopify store</h2>
                            <p class="text-sm leading-relaxed mb-8" style="color: var(--yc-sky);">
                                This links your products, collections and orders to your new app — securely, in one click.
                            </p>
                            <ul class="space-y-4 text-sm">
                                @foreach ([
                                    'No copying API keys — we set everything up for you',
                                    'Your branding & catalog pulled in automatically',
                                    'You sign in on Shopify; we never see your password',
                                ] as $point)
                                    <li class="flex items-start gap-3">
                                        <span class="mt-0.5 flex items-center justify-center h-5 w-5 rounded-full shrink-0" style="background-color: var(--yc-blue);">
                                            <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </span>
                                        <span class="opacity-90">{{ $point }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Right: form --}}
                        <div class="md:col-span-3 p-8 sm:p-10">
                            <h1 class="text-xl font-bold text-gray-900 mb-1">Enter your store address</h1>
                            <p class="text-sm text-gray-500 mb-6">You'll be redirected to Shopify to approve access.</p>

                            <form method="POST" action="{{ route('onboarding.shopify.connect', $tenant) }}">
                                @csrf
                                <label for="shop_domain" class="block text-sm font-medium text-gray-700 mb-2">Shopify store URL</label>
                                <div class="flex rounded-lg shadow-sm @error('shop_domain') ring-1 ring-red-400 @enderror">
                                    <input type="text" name="shop_domain" id="shop_domain"
                                        value="{{ old('shop_domain') }}"
                                        placeholder="your-store"
                                        autofocus
                                        class="flex-1 min-w-0 rounded-l-lg border border-gray-300 border-r-0 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:z-10"
                                        style="--tw-ring-color: var(--yc-blue);">
                                    <span class="inline-flex items-center rounded-r-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-500">
                                        .myshopify.com
                                    </span>
                                </div>
                                @error('shop_domain')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-2 text-xs text-gray-400">You can also paste the full URL — we'll figure it out.</p>

                                <button type="submit"
                                    class="mt-6 inline-flex w-full items-center justify-center gap-2 text-white py-3.5 px-6 rounded-xl transition font-semibold shadow-sm"
                                    style="background-color: var(--yc-navy);"
                                    onmouseover="this.style.backgroundColor='var(--yc-navy-hover)'"
                                    onmouseout="this.style.backgroundColor='var(--yc-navy)'">
                                    Connect with Shopify
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </button>
                            </form>

                            <div class="mt-6 flex items-center gap-2 text-xs text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Secure OAuth connection — authorized directly on Shopify.
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <p class="text-center text-sm text-gray-500 mt-6">
                Need help? <a href="mailto:support@yottocode.com" class="font-medium" style="color: var(--yc-blue);">Contact support</a>
            </p>
        </div>
    </main>

    <footer class="bg-gray-900 text-white py-4">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-300">
            &copy; {{ date('Y') }} Powered by <a href="https://yottocode.com" target="_blank" class="font-medium" style="color: var(--yc-blue);">YottoCode</a>
        </div>
    </footer>
</body>
</html>
