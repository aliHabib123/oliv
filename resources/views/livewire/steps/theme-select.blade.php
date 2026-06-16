<div>
    <h1 class="text-2xl font-bold" style="color: var(--oliv-primary);">Pick a theme</h1>
    <p class="mt-1 text-gray-500">Choose a design template for your app.</p>

    @php
        $themes = \App\Models\AppTheme::all();
        $aiThemeId = $session->ai_recommended['theme_id'] ?? null;
        $themeColors = [
            'Minimal' => '#94a3b8',
            'Bold' => '#ef4444',
            'Classic' => '#a78bfa',
            'Modern' => '#3b82f6',
            'Luxe' => '#f59e0b',
            'Fresh' => '#10b981',
        ];
    @endphp

    <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3">
        @foreach ($themes as $theme)
            <button
                wire:click="selectTheme({{ $theme->id }})"
                class="relative flex flex-col items-center rounded-xl border-2 p-4 transition-all hover:shadow-md"
                @if ($selectedThemeId === $theme->id)
                    style="border-color: var(--oliv-green); background-color: var(--oliv-green-bg);"
                @else
                    style="border-color: #e2e8f0;"
                @endif
            >
                {{-- Theme color block --}}
                <div class="mb-3 flex h-16 w-full items-center justify-center rounded-lg" style="background-color: {{ $themeColors[$theme->name] ?? '#94a3b8' }}20;">
                    <div class="h-8 w-8 rounded-lg" style="background-color: {{ $themeColors[$theme->name] ?? '#94a3b8' }};"></div>
                </div>

                <span class="text-sm font-semibold" style="color: var(--oliv-primary);">{{ $theme->name }}</span>
                <span class="mt-0.5 text-xs text-gray-400">
                    {{ $theme->default_config['card_style'] ?? 'flat' }}
                </span>

                @if ($selectedThemeId === $theme->id)
                    <div class="absolute top-2 right-2 h-5 w-5 rounded-full flex items-center justify-center" style="background-color: var(--oliv-green);">
                        <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                @endif

                @if ($aiThemeId === $theme->id)
                    <span class="absolute -top-2 left-1/2 -translate-x-1/2 rounded px-1.5 py-0.5 text-[9px] font-bold text-white" style="background-color: var(--oliv-accent);">
                        AI Pick
                    </span>
                @endif
            </button>
        @endforeach
    </div>
</div>
