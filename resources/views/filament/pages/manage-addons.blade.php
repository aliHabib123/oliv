<x-filament-panels::page>
    @if($addons->isEmpty())
        <x-filament::section>
            <div class="text-center py-8">
                <p class="text-gray-500">No add-ons available at the moment.</p>
            </div>
        </x-filament::section>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            @foreach($addons as $addon)
                @php
                    $isSubscribed = in_array($addon->id, $subscribedAddons);
                @endphp

                <x-filament::section>
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-semibold">{{ $addon->name }}</h3>
                                @if($isSubscribed)
                                    <x-filament::badge color="success" class="mt-1">
                                        Active
                                    </x-filament::badge>
                                @endif
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xl font-bold">${{ number_format($addon->price, 2) }}</div>
                                <div class="text-xs text-gray-500">per month</div>
                            </div>
                        </div>

                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $addon->description }}</p>

                        @if($isSubscribed)
                            <x-filament::button
                                wire:click="unsubscribe({{ $addon->id }})"
                                color="danger"
                                outlined
                                size="sm"
                                class="w-full"
                            >
                                Unsubscribe
                            </x-filament::button>
                        @else
                            <x-filament::button
                                wire:click="subscribe({{ $addon->id }})"
                                size="sm"
                                class="w-full"
                            >
                                Subscribe
                            </x-filament::button>
                        @endif
                    </div>
                </x-filament::section>
            @endforeach
        </div>

        <x-filament::section icon="heroicon-o-information-circle" icon-color="info">
            <x-slot name="heading">
                About Add-ons
            </x-slot>

            <p class="text-sm">
                Add-ons will enhance your application with additional features. Subscriptions are processed with dummy payment and activated immediately.
            </p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
