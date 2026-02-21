<x-filament-panels::page>
    @push('styles')
        <link href="https://unpkg.com/grapesjs/dist/css/grapes.min.css" rel="stylesheet">
    @endpush

    <div class="space-y-4">
        <!-- Template Info Form -->
        <div class="bg-white rounded-lg shadow p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Template Name *</label>
                    <input type="text" id="templateName" value="{{ $templateName }}" disabled
                        class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                        placeholder="">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                    <input type="text" id="industrySelect" value="{{ $industry }}" disabled
                        class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                        placeholder="">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Style</label>
                    <input type="text" id="styleSelect" value="{{ $style }}" disabled
                        class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                        placeholder="">
                </div>
            </div>
            <div class="flex justify-end">
                <button onclick="saveTemplate()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Save Template
                </button>
            </div>
        </div>

        <!-- GrapesJS Editor -->
        <div class="bg-white rounded-lg shadow" style="height: 700px; min-height: 700px;">
            <div wire:ignore style="height: 100%; width: 100%;">
                <div id="gjs-loading" style="padding: 20px; text-align: center; color: #666;">
                    Loading GrapesJS editor...
                </div>
                <div id="gjs" style="height: 100%; width: 100%;"></div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/grapesjs"></script>
        <script src="https://unpkg.com/grapesjs-preset-webpage"></script>

        <script>
        let editor;

        function initGrapesJS() {
            console.log('Initializing GrapesJS...');
            console.log('GrapesJS available:', typeof grapesjs !== 'undefined');

            if (typeof grapesjs === 'undefined') {
                console.error('GrapesJS not loaded yet, retrying...');
                setTimeout(initGrapesJS, 500);
                return;
            }

            try {
                editor = grapesjs.init({
                container: '#gjs',
                height: '100%',
                width: 'auto',
                storageManager: false,
                plugins: ['gjs-preset-webpage'],
                deviceManager: {
                    devices: [
                        {
                            id: 'mobile-portrait',
                            name: 'Mobile',
                            width: '375px',
                            widthMedia: '375px',
                        },
                    ],
                },
                pluginsOpts: {
                    'gjs-preset-webpage': {
                        blocks: ['link-block', 'quote', 'text-basic'],
                    }
                },
                canvas: {
                    styles: [
                        'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css'
                    ]
                },
                blockManager: {
                    appendTo: '#gjs',
                    blocks: [
                        {
                            id: 'section',
                            label: 'Section',
                            attributes: { class: 'gjs-block-section' },
                            content: `<section style="padding: 40px 20px;">
                                <h2 style="text-align: center; font-size: 2em; margin-bottom: 20px;">Section Title</h2>
                                <p style="text-align: center;">Section content goes here</p>
                            </section>`,
                        },
                        {
                            id: 'text',
                            label: 'Text',
                            content: '<div style="padding: 10px;">Insert your text here</div>',
                        },
                        {
                            id: 'image',
                            label: 'Image',
                            select: true,
                            content: { type: 'image' },
                            activate: true,
                        },
                        {
                            id: 'button',
                            label: 'Button',
                            content: '<a href="#" style="display: inline-block; padding: 10px 20px; background: #615d40; color: white; text-decoration: none; border-radius: 5px;">Button</a>',
                        },
                        {
                            id: 'grid-2',
                            label: '2 Columns',
                            content: `<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 20px;">
                                <div style="padding: 20px; border: 1px solid #ddd;">Column 1</div>
                                <div style="padding: 20px; border: 1px solid #ddd;">Column 2</div>
                            </div>`,
                        },
                        {
                            id: 'grid-3',
                            label: '3 Columns',
                            content: `<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; padding: 20px;">
                                <div style="padding: 20px; border: 1px solid #ddd;">Column 1</div>
                                <div style="padding: 20px; border: 1px solid #ddd;">Column 2</div>
                                <div style="padding: 20px; border: 1px solid #ddd;">Column 3</div>
                            </div>`,
                        },
                    ]
                },
            });

            console.log('GrapesJS initialized successfully!');

            // Default to mobile portrait view
            editor.setDevice('mobile-portrait');

            // Hide loading message
            const loadingEl = document.getElementById('gjs-loading');
            if (loadingEl) {
                loadingEl.style.display = 'none';
            }

            // Load existing template if editing
            @if($templateId)
                setTimeout(function() {
                    loadTemplate();
                }, 500);
            @endif
            } catch (error) {
                console.error('Error initializing GrapesJS:', error);
                const loadingEl = document.getElementById('gjs-loading');
                if (loadingEl) {
                    loadingEl.innerHTML = 'Error loading editor. Check console for details.';
                    loadingEl.style.color = 'red';
                }
            }
        }

        // Call initialization when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initGrapesJS, 1000);
            });
        } else {
            setTimeout(initGrapesJS, 1000);
        }

        function loadTemplate() {
            @this.call('getTemplateData').then(data => {
                if (data && data.gjsData) {
                    editor.loadData(JSON.parse(data.gjsData));
                } else if (data && data.html) {
                    editor.setComponents(data.html);
                    if (data.css) {
                        editor.setStyle(data.css);
                    }
                }
            });
        }

        function saveTemplate() {
            const gjsData = JSON.stringify(editor.getProjectData());
            const html = editor.getHtml();
            const css = editor.getCss();

            @this.call('saveTemplate', {
                gjsData: gjsData,
                html: html,
                css: css
            });
        }
        </script>
    @endpush
</x-filament-panels::page>
