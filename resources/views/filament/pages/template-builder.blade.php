<x-filament-panels::page>
    <style>
        .tb-layout {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            align-items: flex-start;
        }

        .tb-editor {
            flex: 1 1 0%;
            min-width: 0;
        }

        .tb-preview {
            width: 100%;
            flex: 0 0 auto;
            align-self: flex-start;
        }

        .tb-preview-scroll {
            width: 100%;
        }

        @media (min-width: 1024px) {
            .tb-layout {
                flex-direction: row;
            }

            .tb-preview {
                width: 520px;
                position: sticky;
                top: 4.5rem;
                margin-top: 2.2rem;
                overflow: visible;
            }

            .tb-preview-scroll {
                max-height: calc(100vh - 6.7rem);
                overflow-y: auto;
                overscroll-behavior: contain;
            }
        }
    </style>

    <form wire:submit="save">
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

            <div class="tb-layout">
                <div class="tb-editor">
                    {{ $this->form }}
                </div>

                <div class="tb-preview">
                    <div class="tb-preview-scroll">
                        <livewire:home-layout-preview
                            :layoutData="$this->data['layout_data'] ?? []"
                            :tenant="null"
                            :industry="$industry"
                            :key="'template-builder-preview-' . ($this->templateId ?? 'none')"
                        />
                    </div>
                </div>
            </div>

            <div class="pt-4">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                    Save
                </button>
            </div>
        </div>
    </form>
</x-filament-panels::page>
