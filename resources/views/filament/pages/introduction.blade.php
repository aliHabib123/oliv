<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="pt-4">
            <x-filament::actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </div>
    </form>
</x-filament-panels::page>
