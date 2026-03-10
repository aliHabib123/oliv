<div wire:ignore x-data="{
        isUpdating: false,
        previewData: @js($introData ?? []),

        init() {
            // Listen for Livewire events
            Livewire.on('preview-updated', (eventData) => {
                // Livewire 3 passes array with objects
                const data = Array.isArray(eventData) ? eventData[0]?.data : eventData.data;

                this.isUpdating = true;

                // Replace entire object to trigger Alpine Proxy reactivity
                this.previewData = { ...data };

                this.$nextTick(() => {
                    this.isUpdating = false;
                });
            });
        },

        getBannerUrl() {
            const raw = this.previewData.banner_image;

            if (!raw) {
                return null;
            }

            // Reject empty objects {} (Filament sends this during upload)
            if (typeof raw === 'object' && !Array.isArray(raw)) {
                const keys = Object.keys(raw);
                if (keys.length === 0) {
                    return null;
                }
            }

            // Only process strings
            if (typeof raw !== 'string') {
                return null;
            }

            // Full URLs (from temporaryUrl) or relative paths
            return raw.startsWith('http') || raw.startsWith('/') ? raw : '/storage/' + raw;
        },

        getIconUrl(icon) {
            if (!icon) {
                return null;
            }

            let path = icon;

            // Handle array format
            if (Array.isArray(icon)) {
                path = icon[0];
            }
            // Handle object format (Filament uses UUID keys)
            else if (typeof icon === 'object') {
                const values = Object.values(icon);
                path = values[0];
            }

            if (!path || typeof path !== 'string') {
                return null;
            }

            // Already has /storage/ prefix or is a URL
            if (path.startsWith('/storage/') || path.startsWith('http')) {
                return path;
            }

            return '/storage/' + path;
        }
    }"
    @preview-updating.window="isUpdating = true; setTimeout(() => isUpdating = false, 100)">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        [x-cloak] { display: none !important; }

        .phone-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .phone-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .phone-scroll::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 2px;
        }
        .phone-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.3);
        }
    </style>

    <x-filament::section>
        <div style="display: flex; justify-content: center; align-items: center; padding: 2rem; background: #f3f4f6; border-radius: 0.5rem; height: 770px; overflow: hidden;">
            {{-- iPhone Mockup Container --}}
            <div style="position: relative; width: 375px; height: 730px; flex-shrink: 0;">
                {{-- iPhone Frame Image --}}
                <img src="{{ asset('img/apple-iphone-13-pro-max-2021-medium.png') }}"
                     alt="iPhone Frame"
                     style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 2; pointer-events: none;">

                {{-- Screen Content Area --}}
                <div style="position: absolute; top: 15px; left: 16px; right: 16px; bottom: 15px; background: white; border-radius: 40px; overflow-y: scroll; z-index: 1;">
                    <div class="phone-scroll" style="height: 100%; max-height: 100%; overflow-y: scroll; overflow-x: hidden; background: white; transition: opacity 0.2s; -webkit-overflow-scrolling: touch;" :style="isUpdating ? 'opacity: 0.6' : ''">
                        {{-- Content when data exists --}}
                        <div x-show="previewData && Object.keys(previewData).length > 0">
                            {{-- Full Screen Banner Image --}}
                            <div x-show="getBannerUrl()"
                                 style="width: 100%; position: relative; background-color: #f3f4f6;">
                                <img :src="getBannerUrl()"
                                     alt="Banner"
                                     style="width: 100%; height: auto; display: block;">
                            </div>

                            {{-- Welcome Title --}}
                            <div x-show="previewData.welcome_title" style="padding: 2rem 1.5rem 1rem; text-align: center;">
                                <h1 style="font-family: 'Montserrat', sans-serif; font-size: 1.25rem; font-weight: 700; letter-spacing: 0.2em; color: #9B6B4F;" x-text="previewData.welcome_title"></h1>
                            </div>

                            {{-- Welcome Text --}}
                            <div x-show="previewData.welcome_text" style="padding: 0 2rem 2rem; text-align: center;">
                                <p style="font-family: 'Montserrat', sans-serif; font-size: 0.875rem; line-height: 1.75; color: #111827;" x-text="previewData.welcome_text"></p>
                            </div>

                                {{-- Feature Sections --}}
                                <template x-if="previewData.feature_sections && Object.keys(previewData.feature_sections).length > 0">
                                    <div>
                                        <template x-for="(section, sectionIndex) in Object.values(previewData.feature_sections)" :key="sectionIndex">
                                            <div style="padding: 1.5rem;">
                                                <template x-if="section.section_title">
                                                    <h2 style="font-family: 'Montserrat', sans-serif; font-size: 0.875rem; font-weight: 700; letter-spacing: 0.15em; text-align: center; margin-bottom: 2rem; color: #9B6B4F;" x-text="section.section_title"></h2>
                                                </template>
                                                <template x-if="section.items && Object.keys(section.items).length > 0">
                                                    <div style="display: flex; flex-direction: column; gap: 2rem;">
                                                        <template x-for="(item, itemIndex) in Object.values(section.items)" :key="itemIndex">
                                                            <div style="text-align: center;">
                                                                <template x-if="item.icon && getIconUrl(item.icon)">
                                                                    <div style="margin-bottom: 0.75rem; display: flex; justify-content: center;">
                                                                        <img :src="getIconUrl(item.icon)" :alt="item.title || 'Icon'" style="width: 3rem; height: 3rem; object-fit: contain;">
                                                                    </div>
                                                                </template>
                                                                <template x-if="item.title">
                                                                    <h3 style="font-family: 'Montserrat', sans-serif; font-size: 0.875rem; font-weight: 600; letter-spacing: 0.025em; color: #111827;" x-text="item.title"></h3>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                            {{-- Continue Button --}}
                            <div style="padding: 2rem 2rem 3rem;">
                                <button style="width: 100%; padding: 1rem; border-radius: 0.75rem; font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 1rem; color: white; background-color: #9B6B4F; border: none; letter-spacing: 0.05em;">
                                    CONTINUE
                                </button>
                            </div>
                        </div>

                        {{-- Empty State --}}
                        <div x-show="!previewData || Object.keys(previewData).length === 0" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; padding: 3rem 1.5rem;">
                            <svg style="width: 5rem; height: 5rem; color: #d1d5db; margin-bottom: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            <h3 style="font-family: 'Montserrat', sans-serif; font-size: 1.125rem; font-weight: 600; color: #4b5563; margin-bottom: 0.5rem;">No Content Yet</h3>
                            <p style="font-family: 'Montserrat', sans-serif; font-size: 0.875rem; color: #6b7280;">Start adding content to see the preview</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</div>
