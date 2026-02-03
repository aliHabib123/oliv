<div x-data="{
    selectedPreview: null,
    previewData: null,
    previewTitle: ''
}" style="display: flex; flex-direction: column; gap: 2rem;">

    <!-- Templates Section -->
    <div>
        <h4 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <svg style="width: 1.25rem; height: 1.25rem; color: #3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Templates
        </h4>

        @if(!empty($this->availableTemplates))
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                @foreach($this->availableTemplates as $template)
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem; transition: all 0.15s; cursor: pointer;"
                         onmouseover="this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)'; this.style.borderColor='#3b82f6'"
                         onmouseout="this.style.boxShadow=''; this.style.borderColor='#e5e7eb'">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                            <h5 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;">{{ $template['name'] }}</h5>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button
                                type="button"
                                @click="selectedPreview = 'template-{{ $loop->index }}'; previewData = {{ json_encode($template['layout_data']) }}; previewTitle = '{{ $template['name'] }}'"
                                style="flex: 1; padding: 0.5rem; background: #eff6ff; color: #1e40af; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem; border: 1px solid #bfdbfe; cursor: pointer; transition: all 0.15s;"
                                onmouseover="this.style.backgroundColor='#dbeafe'"
                                onmouseout="this.style.backgroundColor='#eff6ff'">
                                Preview
                            </button>
                            <button
                                type="button"
                                wire:click="assignFromTemplate('{{ $handle }}', '{{ $template['filename'] }}')"
                                @click="showAssignModal = false"
                                style="flex: 1; padding: 0.5rem; background: #3b82f6; color: white; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem; border: none; cursor: pointer; transition: all 0.15s;"
                                onmouseover="this.style.backgroundColor='#2563eb'"
                                onmouseout="this.style.backgroundColor='#3b82f6'">
                                Assign
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p style="color: #6b7280; font-size: 0.875rem; padding: 1rem; text-align: center; background: white; border-radius: 0.5rem; border: 1px dashed #d1d5db;">No templates available</p>
        @endif
    </div>

    <!-- Other Categories Section -->
    <div>
        <h4 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <svg style="width: 1.25rem; height: 1.25rem; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            Copy from Other Categories
        </h4>

        @php
            $otherLayouts = collect($this->otherCategoryLayouts)->filter(fn($layout) => $layout['category_handle'] !== $handle);
        @endphp

        @if($otherLayouts->isNotEmpty())
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                @foreach($otherLayouts as $layout)
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem; transition: all 0.15s; cursor: pointer;"
                         onmouseover="this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)'; this.style.borderColor='#10b981'"
                         onmouseout="this.style.boxShadow=''; this.style.borderColor='#e5e7eb'">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                            <div>
                                <h5 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;">{{ $layout['category_title'] }}</h5>
                                <p style="font-size: 0.75rem; color: #6b7280; margin: 0.25rem 0 0 0;">{{ $layout['name'] }}</p>
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button
                                type="button"
                                @click="selectedPreview = 'category-{{ $loop->index }}'; previewData = {{ json_encode($layout['layout_data']) }}; previewTitle = '{{ $layout['category_title'] }} - {{ $layout['name'] }}'"
                                style="flex: 1; padding: 0.5rem; background: #f0fdf4; color: #166534; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem; border: 1px solid #bbf7d0; cursor: pointer; transition: all 0.15s;"
                                onmouseover="this.style.backgroundColor='#dcfce7'"
                                onmouseout="this.style.backgroundColor='#f0fdf4'">
                                Preview
                            </button>
                            <button
                                type="button"
                                wire:click="assignFromCategory('{{ $handle }}', '{{ $layout['category_handle'] }}')"
                                @click="showAssignModal = false"
                                style="flex: 1; padding: 0.5rem; background: #10b981; color: white; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem; border: none; cursor: pointer; transition: all 0.15s;"
                                onmouseover="this.style.backgroundColor='#059669'"
                                onmouseout="this.style.backgroundColor='#10b981'">
                                Copy
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p style="color: #6b7280; font-size: 0.875rem; padding: 1rem; text-align: center; background: white; border-radius: 0.5rem; border: 1px dashed #d1d5db;">No other category layouts available yet</p>
        @endif
    </div>

    <!-- Preview Modals for Templates -->
    @foreach($this->availableTemplates as $index => $template)
        <div x-show="selectedPreview === 'template-{{ $index }}'"
             x-cloak
             @click="selectedPreview = null"
             @keydown.escape.window="selectedPreview = null"
             style="position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; z-index: 10000; background: rgba(0, 0, 0, 0.75); display: flex; align-items: center; justify-content: center; padding: 1rem;">
            <div @click.stop
                 style="background: white; border-radius: 0.75rem; width: 90%; max-width: 50rem; max-height: 85vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">

                <!-- Preview Header -->
                <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: #f9fafb;">
                    <h4 style="font-size: 1rem; font-weight: 600; color: #111827; margin: 0;">{{ $template['name'] }}</h4>
                    <button
                        type="button"
                        @click="selectedPreview = null"
                        style="padding: 0.5rem; background: transparent; border: none; color: #6b7280; cursor: pointer; border-radius: 0.375rem; transition: all 0.15s;"
                        onmouseover="this.style.backgroundColor='#f3f4f6'"
                        onmouseout="this.style.backgroundColor='transparent'">
                        <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Preview Body -->
                <div style="padding: 1.5rem; overflow-y: auto; max-height: calc(85vh - 8rem); background: #f3f4f6; display: flex; justify-content: center; align-items: center;">
                    @include('filament.pages.partials.layout-preview-phone', ['layoutData' => $template['layout_data']])
                </div>
            </div>
        </div>
    @endforeach

    <!-- Preview Modals for Other Categories -->
    @php
        $otherLayouts = collect($this->otherCategoryLayouts)->filter(fn($layout) => $layout['category_handle'] !== $handle);
    @endphp
    @foreach($otherLayouts as $index => $layout)
        <div x-show="selectedPreview === 'category-{{ $index }}'"
             x-cloak
             @click="selectedPreview = null"
             @keydown.escape.window="selectedPreview = null"
             style="position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; z-index: 10000; background: rgba(0, 0, 0, 0.75); display: flex; align-items: center; justify-content: center; padding: 1rem;">
            <div @click.stop
                 style="background: white; border-radius: 0.75rem; width: 90%; max-width: 50rem; max-height: 85vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">

                <!-- Preview Header -->
                <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: #f9fafb;">
                    <h4 style="font-size: 1rem; font-weight: 600; color: #111827; margin: 0;">{{ $layout['category_title'] }} - {{ $layout['name'] }}</h4>
                    <button
                        type="button"
                        @click="selectedPreview = null"
                        style="padding: 0.5rem; background: transparent; border: none; color: #6b7280; cursor: pointer; border-radius: 0.375rem; transition: all 0.15s;"
                        onmouseover="this.style.backgroundColor='#f3f4f6'"
                        onmouseout="this.style.backgroundColor='transparent'">
                        <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Preview Body -->
                <div style="padding: 1.5rem; overflow-y: auto; max-height: calc(85vh - 8rem); background: #f3f4f6; display: flex; justify-content: center; align-items: center;">
                    @include('filament.pages.partials.layout-preview-phone', ['layoutData' => $layout['layout_data']])
                </div>
            </div>
        </div>
    @endforeach
</div>
