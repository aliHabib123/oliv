<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        <div class="space-y-4">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Template Name</label>
                        <input type="text" value="{{ $templateName }}" disabled
                            class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                            placeholder="">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                        <input type="text" value="{{ $industry }}" disabled
                            class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                            placeholder="">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Style</label>
                        <input type="text" value="{{ $style }}" disabled
                            class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                            placeholder="">
                    </div>
                </div>
            </div>

            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
