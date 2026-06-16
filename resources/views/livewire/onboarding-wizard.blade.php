<div>
    {{-- Progress Bar --}}
    <div class="mb-2">
        <div class="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500" style="width: {{ ($currentStep / $totalSteps) * 100 }}%; background-color: var(--oliv-green);"></div>
        </div>
    </div>

    {{-- Step Dots --}}
    <div class="mb-8 flex items-center gap-2">
        @for ($i = 1; $i <= $totalSteps; $i++)
            <div class="h-3 w-3 rounded-full transition-colors"
                @if ($i === $currentStep)
                    style="background-color: var(--oliv-green);"
                @elseif ($i < $currentStep)
                    style="background-color: var(--oliv-green); opacity: 0.5;"
                @else
                    style="background-color: #e2e8f0;"
                @endif
            ></div>
        @endfor
    </div>

    {{-- Step Content --}}
    @if ($currentStep == 1)
        @include('livewire.steps.platform-select')
    @elseif ($currentStep == 2)
        @include('livewire.steps.shopify-connect')
    @elseif ($currentStep == 3)
        @include('livewire.steps.data-review')
    @elseif ($currentStep == 4)
        @include('livewire.steps.branding')
    {{-- @elseif ($currentStep == 5)
        @include('livewire.steps.features') --}}
    @elseif ($currentStep == 5)
        @include('livewire.steps.theme-select')
    @elseif ($currentStep == 6)
        @include('livewire.steps.review-generate')
    @elseif ($currentStep == 7)
        @include('livewire.steps.completion')
    @endif

    {{-- Footer Navigation --}}
    @if ($currentStep > 1 && $currentStep < 7)
        <div class="mt-8 flex items-center justify-between border-t border-gray-100 pt-6">
            <span class="text-sm text-gray-400">Step {{ $currentStep }} of {{ $totalSteps }}</span>

            <div class="flex items-center gap-3">
                <button
                    wire:click="previousStep"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
                >
                    ← Back
                </button>

                @if ($currentStep !== 2 && $currentStep !== 6)
                    <button
                        wire:click="nextStep"
                        class="rounded-lg px-5 py-2 text-sm font-medium text-white transition-colors"
                        style="background-color: var(--oliv-action);"
                        onmouseover="this.style.backgroundColor='var(--oliv-action-hover)'"
                        onmouseout="this.style.backgroundColor='var(--oliv-action)'"
                    >
                        @if ($currentStep === 3)
                            Confirm →
                        @elseif ($currentStep === 4)
                            Save & continue →
                        @else
                            Continue →
                        @endif
                    </button>
                @endif
            </div>
        </div>
    @elseif ($currentStep == 1)
        <div class="mt-8 flex items-center justify-between border-t border-gray-100 pt-6">
            <span class="text-sm text-gray-400">Step {{ $currentStep }} of {{ $totalSteps }}</span>
            <div></div>
        </div>
    @endif
</div>
