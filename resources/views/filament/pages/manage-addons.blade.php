<x-filament-panels::page>
    @if($addons->isEmpty())
        <x-filament::section>
            <div class="text-center py-8">
                <p class="text-gray-500">No add-ons available at the moment.</p>
            </div>
        </x-filament::section>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            @foreach($addons as $addon)
                @php
                    $isSubscribed = in_array($addon->id, $subscribedAddons);
                @endphp

                <x-filament::section>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1.5rem;">
                        <div style="flex: 1; min-width: 0;">
                            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">{{ $addon->name }}</h3>

                            @if($isSubscribed)
                                <div style="margin-bottom: 0.75rem;">
                                    <x-filament::badge color="success" icon="heroicon-m-check-circle">
                                        Active
                                    </x-filament::badge>
                                </div>
                            @endif

                            <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem; line-height: 1.5;">{{ $addon->description }}</p>

                            <div style="margin-top: 0.75rem;">
                                <div style="font-size: 1.5rem; font-weight: 700; line-height: 1;">${{ number_format($addon->price, 2) }}</div>
                                <div style="font-size: 0.75rem; color: #9ca3af;">per month</div>
                            </div>
                        </div>

                        <div style="flex-shrink: 0;">
                            @if($isSubscribed)
                                <x-filament::button
                                    wire:click="unsubscribe({{ $addon->id }})"
                                    color="danger"
                                    outlined
                                    size="sm"
                                >
                                    Unsubscribe
                                </x-filament::button>
                            @else
                                <x-filament::button
                                    wire:click="subscribe({{ $addon->id }})"
                                    size="sm"
                                >
                                    Subscribe
                                </x-filament::button>
                            @endif
                        </div>
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
