<div x-data="{
    selectedPreview: null,
    previewData: null,
    previewTitle: ''
}" style="display: flex; flex-direction: column; gap: 2rem;">

    @php
        $handle = $handle ?? '';
    @endphp

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
                            <div>
                                <h5 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;">{{ $template['name'] }}</h5>
                                @if($template['type'] === 'tenant')
                                    <span style="font-size: 0.625rem; color: #059669; background: #d1fae5; padding: 0.125rem 0.375rem; border-radius: 0.25rem; display: inline-block; margin-top: 0.25rem;">Current Layout</span>
                                @endif
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button
                                type="button"
                                @if($template['type'] === 'tenant')
                                    wire:click="openTemplatePreview('tenant', 'tenant')"
                                @elseif($template['type'] === 'db')
                                    wire:click="openTemplatePreview('db', '{{ $template['template_id'] }}')"
                                @else
                                    wire:click="openTemplatePreview('file', '{{ $template['filename'] }}')"
                                @endif
                                style="flex: 1; padding: 0.5rem; background: #eff6ff; color: #1e40af; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem; border: 1px solid #bfdbfe; cursor: pointer; transition: all 0.15s;"
                                onmouseover="this.style.backgroundColor='#dbeafe'"
                                onmouseout="this.style.backgroundColor='#eff6ff'">
                                Preview
                            </button>
                            <button
                                type="button"
                                @if($template['type'] === 'tenant')
                                    wire:click="assignFromTemplate('{{ $handle }}', 'tenant', 'tenant')"
                                @elseif($template['type'] === 'db')
                                    wire:click="assignFromTemplate('{{ $handle }}', '{{ $template['template_id'] }}', 'db')"
                                @else
                                    wire:click="assignFromTemplate('{{ $handle }}', '{{ $template['filename'] }}', 'file')"
                                @endif
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

    <!-- Library Section -->
    <div>
        <h4 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <svg style="width: 1.25rem; height: 1.25rem; color: #8b5cf6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            Library
        </h4>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Industry</label>
                <select wire:model.live="libraryIndustry" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: white; font-size: 0.875rem;">
                    <option value="">All</option>
                    <option value="restaurant">Restaurant</option>
                    <option value="retail">Retail</option>
                    <option value="healthcare">Healthcare</option>
                    <option value="education">Education</option>
                    <option value="real_estate">Real Estate</option>
                    <option value="technology">Technology</option>
                    <option value="fashion">Fashion</option>
                    <option value="automotive">Automotive</option>
                    <option value="sports">Sports</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Style</label>
                <select wire:model.live="libraryStyle" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: white; font-size: 0.875rem;">
                    <option value="">All</option>
                    <option value="modern">Modern</option>
                    <option value="classic">Classic</option>
                    <option value="minimal">Minimal</option>
                    <option value="bold">Bold</option>
                    <option value="elegant">Elegant</option>
                    <option value="playful">Playful</option>
                </select>
            </div>
        </div>

        @php
            $libraryTemplates = collect($this->libraryTemplates)
                ->when(! empty($this->libraryIndustry), fn ($q) => $q->filter(fn ($t) => ($t['industry'] ?? null) === $this->libraryIndustry))
                ->when(! empty($this->libraryStyle), fn ($q) => $q->filter(fn ($t) => ($t['style'] ?? null) === $this->libraryStyle))
                ->values();
        @endphp

        @if($libraryTemplates->isNotEmpty())
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                @foreach($libraryTemplates as $template)
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem; transition: all 0.15s;"
                         onmouseover="this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)'; this.style.borderColor='#8b5cf6'"
                         onmouseout="this.style.boxShadow=''; this.style.borderColor='#e5e7eb'">
                        <div style="display: flex; gap: 0.75rem; margin-bottom: 0.75rem;">
                            <div style="width: 56px; height: 56px; border-radius: 0.5rem; overflow: hidden; background: #f3f4f6; border: 1px solid #e5e7eb; flex-shrink: 0;">
                                @if(! empty($template['preview_image']))
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($template['preview_image']) }}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                                @else
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 0.75rem;">No image</div>
                                @endif
                            </div>
                            <div style="min-width: 0; flex: 1;">
                                <h5 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $template['name'] }}</h5>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.25rem;">
                                    @if(! empty($template['industry']))
                                        <span style="font-size: 0.625rem; color: #5b21b6; background: #ede9fe; padding: 0.125rem 0.375rem; border-radius: 0.25rem; display: inline-block;">{{ $template['industry'] }}</span>
                                    @endif
                                    @if(! empty($template['style']))
                                        <span style="font-size: 0.625rem; color: #0f766e; background: #ccfbf1; padding: 0.125rem 0.375rem; border-radius: 0.25rem; display: inline-block;">{{ $template['style'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 0.5rem;">
                            <button
                                type="button"
                                wire:click="openTemplatePreview('db', '{{ $template['id'] }}')"
                                style="flex: 1; padding: 0.5rem; background: #f5f3ff; color: #5b21b6; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem; border: 1px solid #ddd6fe; cursor: pointer; transition: all 0.15s;"
                                onmouseover="this.style.backgroundColor='#ede9fe'"
                                onmouseout="this.style.backgroundColor='#f5f3ff'">
                                Preview
                            </button>
                            <button
                                type="button"
                                wire:click="assignFromTemplate('{{ $handle }}', '{{ $template['id'] }}', 'db')"
                                @click="showAssignModal = false"
                                style="flex: 1; padding: 0.5rem; background: #8b5cf6; color: white; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem; border: none; cursor: pointer; transition: all 0.15s;"
                                onmouseover="this.style.backgroundColor='#7c3aed'"
                                onmouseout="this.style.backgroundColor='#8b5cf6'">
                                Assign
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p style="color: #6b7280; font-size: 0.875rem; padding: 1rem; text-align: center; background: white; border-radius: 0.5rem; border: 1px dashed #d1d5db;">No templates match your filters</p>
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
                                wire:click="openCategoryPreview('{{ $layout['category_handle'] }}')"
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

</div>
