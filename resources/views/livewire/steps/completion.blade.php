<div class="py-8 text-center">
    {{-- Success icon --}}
    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full" style="background-color: var(--oliv-green-bg);">
        <svg class="h-7 w-7" style="color: var(--oliv-green);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
        </svg>
    </div>

    <h1 class="mt-5 text-2xl font-bold" style="color: var(--oliv-primary);">Setup complete!</h1>
    <p class="mt-2 text-gray-500 max-w-sm mx-auto">
        Your app configuration has been saved. You can manage everything from your dashboard.
    </p>

    <div class="mt-8 mx-auto max-w-xs rounded-lg border border-gray-200 p-4 text-left">
        <ul class="space-y-2 text-sm">
            <li class="flex items-center gap-2">
                <svg class="h-4 w-4" style="color: var(--oliv-green);" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                <span class="text-gray-600">Store connected</span>
            </li>
            <li class="flex items-center gap-2">
                <svg class="h-4 w-4" style="color: var(--oliv-green);" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                <span class="text-gray-600">Branding configured</span>
            </li>
            <li class="flex items-center gap-2">
                <svg class="h-4 w-4" style="color: var(--oliv-green);" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                <span class="text-gray-600">App configuration saved</span>
            </li>
        </ul>
    </div>

    <a
        href="/admin"
        class="mt-8 inline-flex items-center gap-2 rounded-lg px-6 py-2.5 text-sm font-medium text-white transition-colors"
        style="background-color: var(--oliv-action);"
        onmouseover="this.style.backgroundColor='var(--oliv-action-hover)'"
        onmouseout="this.style.backgroundColor='var(--oliv-action)'"
    >
        Go to Dashboard →
    </a>
</div>
