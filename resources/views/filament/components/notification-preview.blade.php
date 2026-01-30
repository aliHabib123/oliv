<div x-data="{ isUpdating: false }"
     @preview-updating.window="isUpdating = true; setTimeout(() => isUpdating = false, 100)">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');

        .notification-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .notification-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .notification-scroll::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 2px;
        }
        .notification-scroll::-webkit-scrollbar-thumb:hover {
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
                    <div class="notification-scroll" style="height: 100%; max-height: 100%; overflow-y: scroll; overflow-x: hidden; background: #f9fafb; transition: opacity 0.2s; -webkit-overflow-scrolling: touch; position: relative;" :style="isUpdating ? 'opacity: 0.6' : ''">
                        @php
                            $messageType = $notificationData['message_type'] ?? 'center';
                            $heading = $notificationData['message_heading'] ?? 'Heading';
                            $bodyText = $notificationData['body_text'] ?? '';
                            $buttonText = $notificationData['button_text'] ?? 'Click Me';
                            $imageUrlRaw = $notificationData['image_url'] ?? null;
                            $imageUrl = is_array($imageUrlRaw) ? ($imageUrlRaw[0] ?? null) : $imageUrlRaw;
                            $bgColor = $notificationData['background_color'] ?? '#FFFFFF';
                            $headingColor = $notificationData['heading_color'] ?? '#222222';
                            $bodyColor = $notificationData['body_color'] ?? '#999999';
                            $buttonBg = $notificationData['button_background'] ?? '#1f8feb';
                            $buttonTextColor = $notificationData['button_text_color'] ?? '#ffffff';

                            // Position styles based on message type
                            $containerStyle = '';
                            $notificationStyle = '';

                            $showFullContent = in_array($messageType, ['center', 'full']);

                            switch($messageType) {
                                case 'top':
                                    $containerStyle = 'padding: 1rem 1rem 0 1rem; justify-content: flex-start;';
                                    $notificationStyle = 'width: 100%; border-radius: 0.5rem;';
                                    break;
                                case 'bottom':
                                    $containerStyle = 'padding: 0 1rem 1rem 1rem; justify-content: flex-end;';
                                    $notificationStyle = 'width: 100%; border-radius: 0.5rem;';
                                    break;
                                case 'full':
                                    $containerStyle = 'padding: 0;';
                                    $notificationStyle = 'width: 100%; height: 100%; border-radius: 0; max-width: 100%;';
                                    break;
                                case 'center':
                                default:
                                    $containerStyle = 'justify-content: center; align-items: center; padding: 1rem;';
                                    $notificationStyle = 'max-width: 90%; border-radius: 0.75rem;';
                                    break;
                            }
                        @endphp

                        {{-- Notification Container --}}
                        <div style="display: flex; flex-direction: column; min-height: 100%; {{ $containerStyle }}">
                            {{-- Notification Card --}}
                            <div style="background: {{ $bgColor }}; padding: 1.5rem; {{ $notificationStyle }} box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);">
                                {{-- Close Button --}}
                                <div style="display: flex; justify-content: flex-end; margin-bottom: 0.5rem;">
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: rgba(0, 0, 0, 0.1); display: flex; align-items: center; justify-content: center;">
                                        <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9.5 1L1 9.5M1 1l8.5 8.5" stroke="#666" stroke-width="1.5" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                </div>

                                {{-- Heading --}}
                                @if($heading)
                                    <h1 style="color: {{ $headingColor }}; font-size: 1.25rem; font-weight: 600; margin: 0 {{ $showFullContent ? '0 0.75rem 0' : '0' }}; text-align: center;">
                                        {{ $heading }}
                                    </h1>
                                @endif

                                @if($showFullContent)
                                    {{-- Image --}}
                                    @if($imageUrl)
                                        <div style="margin-bottom: 1rem; display: flex; justify-content: center;">
                                            <img src="{{ Storage::disk('public')->url($imageUrl) }}"
                                                 alt="Notification Image"
                                                 style="max-width: 100%; height: auto; border-radius: 0.5rem;">
                                        </div>
                                    @else
                                        {{-- Placeholder Image --}}
                                        <div style="margin: 1rem 0; background: #e5e7eb; border-radius: 0.5rem; height: 200px; display: flex; align-items: center; justify-content: center;">
                                            <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="30" cy="25" r="8" fill="white"/>
                                                <path d="M10 60L25 45L35 55L50 35L70 55V65C70 67.7614 67.7614 70 65 70H15C12.2386 70 10 67.7614 10 65V60Z" fill="white"/>
                                            </svg>
                                        </div>
                                    @endif

                                    {{-- Body Text --}}
                                    @if($bodyText)
                                        <p style="color: {{ $bodyColor }}; font-size: 0.875rem; margin: 0 0 1rem 0; text-align: center; line-height: 1.5;">
                                            {{ $bodyText }}
                                        </p>
                                    @endif

                                    {{-- Button --}}
                                    @if($buttonText)
                                        <button style="width: 100%; background: {{ $buttonBg }}; color: {{ $buttonTextColor }}; padding: 0.75rem; border: none; border-radius: 0.375rem; font-size: 1rem; font-weight: 500; cursor: pointer;">
                                            {{ $buttonText }}
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</div>
