<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex gap-4">
            <x-filament::button wire:click="refresh" icon="heroicon-o-arrow-path">
                Refresh
            </x-filament::button>
            <x-filament::button wire:click="clearLogs" color="danger" icon="heroicon-o-trash">
                Clear Logs
            </x-filament::button>
        </div>

        <div class="bg-gray-900 rounded-lg p-4 overflow-auto max-h-[70vh]">
            <pre class="text-green-400 text-sm font-mono whitespace-pre-wrap">{{ $logContent }}</pre>
        </div>
    </div>
</x-filament-panels::page>
