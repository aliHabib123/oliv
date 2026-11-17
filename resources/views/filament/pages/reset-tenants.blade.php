<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Danger notice --}}
        <div class="rounded-xl border border-danger-300 bg-danger-50 p-5 dark:border-danger-500/30 dark:bg-danger-500/10">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-6 w-6 text-danger-600 dark:text-danger-400 shrink-0" />
                <div class="text-sm text-danger-800 dark:text-danger-200">
                    <p class="font-semibold">This is a destructive action.</p>
                    <p class="mt-1">
                        Use “Delete all tenants” to wipe every tenant and all of their data so you can start fresh.
                        Super-admin accounts and global catalogue data (packages, add-ons, countries, pricing) are kept.
                        This cannot be undone.
                    </p>
                </div>
            </div>
        </div>

        {{-- What will be deleted --}}
        <x-filament::section>
            <x-slot name="heading">What will be deleted</x-slot>
            <x-slot name="description">Current record counts that the reset will remove.</x-slot>

            @if (array_sum($counts) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">Nothing to delete — there are no tenants right now.</p>
            @else
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($counts as $label => $count)
                        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="text-2xl font-bold {{ $count > 0 ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-600' }}">
                                {{ number_format($count) }}
                            </div>
                            <div class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
