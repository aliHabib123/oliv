<div>
    <h1 class="text-2xl font-bold" style="color: var(--oliv-primary);">Review & generate</h1>
    <p class="mt-1 text-gray-500">Everything looks good? Hit generate to build your app.</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        {{-- Platform --}}
        <div class="rounded-lg border border-gray-200 p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Platform</p>
            <p class="mt-1 text-sm font-semibold" style="color: var(--oliv-primary);">{{ ucfirst($platform) }}</p>
        </div>

        {{-- Theme --}}
        <div class="rounded-lg border border-gray-200 p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Theme</p>
            @php $selectedTheme = $selectedThemeId ? \App\Models\AppTheme::find($selectedThemeId) : null; @endphp
            <p class="mt-1 text-sm font-semibold" style="color: var(--oliv-primary);">{{ $selectedTheme?->name ?? 'None' }}</p>
        </div>

        {{-- Branding --}}
        <div class="rounded-lg border border-gray-200 p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Branding</p>
            <p class="mt-1 text-sm font-semibold" style="color: var(--oliv-primary);">{{ $branding['app_name'] ?? 'Not set' }}</p>
            <div class="mt-1.5 flex items-center gap-1.5">
                @if (!empty($branding['primary_color']))
                    <span class="h-4 w-4 rounded border border-gray-200" style="background-color: {{ $branding['primary_color'] }};"></span>
                @endif
                @if (!empty($branding['secondary_color']))
                    <span class="h-4 w-4 rounded border border-gray-200" style="background-color: {{ $branding['secondary_color'] }};"></span>
                @endif
                <span class="text-xs text-gray-400">{{ $branding['font_family'] ?? 'Inter' }}</span>
            </div>
        </div>

        {{-- Features --}}
        {{-- Features section removed --}}
    </div>

    {{-- Generate Button --}}
    <button
        wire:click="generate"
        wire:loading.attr="disabled"
        class="mt-8 w-full rounded-lg px-6 py-3 text-base font-semibold text-white transition-colors disabled:opacity-60"
        style="background-color: var(--oliv-action);"
        onmouseover="this.style.backgroundColor='var(--oliv-action-hover)'"
        onmouseout="this.style.backgroundColor='var(--oliv-action)'"
    >
        <span wire:loading.remove>Generate My App →</span>
        <span wire:loading class="inline-flex items-center gap-2">
            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Generating...
        </span>
    </button>
</div>
