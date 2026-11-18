<!DOCTYPE html>
<html lang="en" style="height: 100%;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're all set — YottoCode</title>
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
        <div class="max-w-2xl mx-auto">

            {{-- Step indicator (all complete) --}}
            <div class="flex items-center justify-center gap-2 mb-8 text-xs font-medium text-gray-500">
                @php $steps = ['Connect store', 'Branding', 'Categories', 'Store info', 'Finish']; $current = 4; @endphp
                @foreach ($steps as $i => $label)
                    <div class="flex items-center gap-2">
                        <span class="flex items-center justify-center h-6 w-6 rounded-full text-white text-[11px] font-semibold"
                            style="background-color: {{ $i < $current ? '#10b981' : 'var(--yc-blue-strong)' }};">
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

            <div class="bg-white rounded-2xl shadow-lg p-8 sm:p-10 text-center">
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-6" style="background-color: rgba(16, 185, 129, 0.1);">
                    <svg class="h-11 w-11 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">You're all set!</h1>
                <p class="text-gray-600 mb-8">
                    <span class="font-semibold text-gray-900">{{ $appName }}</span> is configured and ready.
                </p>

                <div class="rounded-xl bg-gray-50 border border-gray-100 p-5 text-left mb-8">
                    <p class="text-sm font-semibold text-gray-900 mb-3">What happens next:</p>
                    <ul class="space-y-2.5 text-sm text-gray-600">
                        <li class="flex items-start gap-2">
                            <svg class="h-5 w-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Your store, branding and categories are saved.
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="h-5 w-5 {{ $storefrontReady ? 'text-emerald-500' : 'text-amber-500' }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Catalog access for the mobile app {{ $storefrontReady ? 'is ready' : '(our team will finish setting up)' }}.
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="h-5 w-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Fine-tune everything any time from your dashboard.
                        </li>
                    </ul>
                </div>

                <a href="/admin/login"
                    class="yc-btn inline-flex w-full items-center justify-center py-4 px-6 transition font-semibold text-lg">
                    Go to your dashboard
                </a>
                <p class="mt-4 text-sm text-gray-500">Log in with the email and password you used at sign-up.</p>
            </div>
        </div>
    </main>

    <footer class="bg-gray-900 text-white py-4">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-300">
            &copy; {{ date('Y') }} Powered by <a href="https://yottocode.com" target="_blank" class="font-medium" style="color: var(--yc-blue-strong);">YottoCode</a>
        </div>
    </footer>
</body>
</html>
