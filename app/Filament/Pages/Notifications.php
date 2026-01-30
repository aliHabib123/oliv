<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Livewire as LivewireComponent;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class Notifications extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bell';

    protected string $view = 'filament.pages.notifications';

    protected static string | \UnitEnum | null $navigationGroup = 'Marketing';

    protected static int | null $navigationSort = 2;

    public ?string $message_type = 'center';
    public ?string $message_heading = 'Heading';
    public ?string $body_text = '';
    public ?string $button_text = 'Click Me';
    public array|string|null $image_url = null;
    public ?string $background_color = '#F8F8F8';
    public ?string $heading_color = '#222222';
    public ?string $body_color = '#999999';
    public ?string $button_background = '#1f8feb';
    public ?string $button_text_color = '#ffffff';

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getImageUrl()
    {
        if (!$this->image_url) {
            return null;
        }

        // Handle TemporaryUploadedFile object
        if (is_object($this->image_url) && method_exists($this->image_url, 'temporaryUrl')) {
            return $this->image_url->temporaryUrl();
        }

        // Handle array of files
        if (is_array($this->image_url)) {
            if (empty($this->image_url)) {
                return null;
            }

            // Get first value from array (might have UUID keys, not numeric)
            $firstFile = reset($this->image_url);

            // Check if it's a TemporaryUploadedFile object
            if (is_object($firstFile) && method_exists($firstFile, 'temporaryUrl')) {
                return $firstFile->temporaryUrl();
            }

            // Check if it's a string path (permanent storage)
            if (is_string($firstFile)) {
                return asset('storage/' . $firstFile);
            }
        }

        // Handle string path (permanent storage)
        if (is_string($this->image_url)) {
            return asset('storage/' . $this->image_url);
        }

        return null;
    }

    protected function getFormSchema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    Section::make()
                        ->schema([
                            Forms\Components\Placeholder::make('message_type_selector')
                                ->content(fn () => view('filament.components.message-type-selector'))
                                ->columnSpanFull(),

                            Forms\Components\Hidden::make('message_type')
                                ->default('center')
                                ->live()
                                ->afterStateUpdated(function () {
                                    $this->dispatch('updatePreview', data: [
                                        'message_type' => $this->message_type,
                                        'message_heading' => $this->message_heading,
                                        'body_text' => $this->body_text,
                                        'button_text' => $this->button_text,
                                        'image_url' => $this->getImageUrl(),
                                        'background_color' => $this->background_color,
                                        'heading_color' => $this->heading_color,
                                        'body_color' => $this->body_color,
                                        'button_background' => $this->button_background,
                                        'button_text_color' => $this->button_text_color,
                                    ]);
                                }),

                            Forms\Components\TextInput::make('message_heading')
                                ->label('Heading')
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function () {
                                    $this->dispatch('updatePreview', data: [
                                        'message_type' => $this->message_type,
                                        'message_heading' => $this->message_heading,
                                        'body_text' => $this->body_text,
                                        'button_text' => $this->button_text,
                                        'image_url' => $this->getImageUrl(),
                                        'background_color' => $this->background_color,
                                        'heading_color' => $this->heading_color,
                                        'body_color' => $this->body_color,
                                        'button_background' => $this->button_background,
                                        'button_text_color' => $this->button_text_color,
                                    ]);
                                })
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('body_text')
                                ->label('Body Text')
                                ->rows(3)
                                ->live(onBlur: true)
                                ->visible(fn () => in_array($this->message_type, ['center', 'full']))
                                ->afterStateUpdated(function () {
                                    $this->dispatch('updatePreview', data: [
                                        'message_type' => $this->message_type,
                                        'message_heading' => $this->message_heading,
                                        'body_text' => $this->body_text,
                                        'button_text' => $this->button_text,
                                        'image_url' => $this->getImageUrl(),
                                        'background_color' => $this->background_color,
                                        'heading_color' => $this->heading_color,
                                        'body_color' => $this->body_color,
                                        'button_background' => $this->button_background,
                                        'button_text_color' => $this->button_text_color,
                                    ]);
                                })
                                ->columnSpanFull(),

                            Forms\Components\FileUpload::make('image_url')
                                ->label('Image')
                                ->image()
                                ->disk('public')
                                ->directory('notifications')
                                ->live()
                                ->visible(fn () => in_array($this->message_type, ['center', 'full']))
                                ->afterStateUpdated(function ($state, $component) {
                                    // Extract livewire temporary file URL from state
                                    $imageUrl = null;

                                    // State is a TemporaryUploadedFile object
                                    if (is_object($state) && method_exists($state, 'temporaryUrl')) {
                                        $imageUrl = $state->temporaryUrl();
                                    } elseif (is_array($state) && !empty($state)) {
                                        // Handle array of files (multi-upload)
                                        $firstFile = $state[0] ?? null;
                                        if (is_object($firstFile) && method_exists($firstFile, 'temporaryUrl')) {
                                            $imageUrl = $firstFile->temporaryUrl();
                                        }
                                    }

                                    $this->dispatch('updatePreview', data: [
                                        'message_type' => $this->message_type,
                                        'message_heading' => $this->message_heading,
                                        'body_text' => $this->body_text,
                                        'button_text' => $this->button_text,
                                        'image_url' => $imageUrl,
                                        'background_color' => $this->background_color,
                                        'heading_color' => $this->heading_color,
                                        'body_color' => $this->body_color,
                                        'button_background' => $this->button_background,
                                        'button_text_color' => $this->button_text_color,
                                    ]);
                                })
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('button_text')
                                ->label('Button Text')
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->visible(fn () => in_array($this->message_type, ['center', 'full']))
                                ->afterStateUpdated(function () {
                                    $this->dispatch('updatePreview', data: [
                                        'message_type' => $this->message_type,
                                        'message_heading' => $this->message_heading,
                                        'body_text' => $this->body_text,
                                        'button_text' => $this->button_text,
                                        'image_url' => $this->getImageUrl(),
                                        'background_color' => $this->background_color,
                                        'heading_color' => $this->heading_color,
                                        'body_color' => $this->body_color,
                                        'button_background' => $this->button_background,
                                        'button_text_color' => $this->button_text_color,
                                    ]);
                                })
                                ->columnSpanFull(),

                            Grid::make(2)
                                ->schema([
                                    Forms\Components\ColorPicker::make('background_color')
                                        ->label('Background Color')
                                        ->default('#F8F8F8')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function () {
                                            $this->dispatch('updatePreview', data: [
                                                'message_type' => $this->message_type,
                                                'message_heading' => $this->message_heading,
                                                'body_text' => $this->body_text,
                                                'button_text' => $this->button_text,
                                                'image_url' => $this->getImageUrl(),
                                                'background_color' => $this->background_color,
                                                'heading_color' => $this->heading_color,
                                                'body_color' => $this->body_color,
                                                'button_background' => $this->button_background,
                                                'button_text_color' => $this->button_text_color,
                                            ]);
                                        }),

                                    Forms\Components\ColorPicker::make('heading_color')
                                        ->label('Heading Color')
                                        ->default('#222222')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function () {
                                            $this->dispatch('updatePreview', data: [
                                                'message_type' => $this->message_type,
                                                'message_heading' => $this->message_heading,
                                                'body_text' => $this->body_text,
                                                'button_text' => $this->button_text,
                                                'image_url' => $this->getImageUrl(),
                                                'background_color' => $this->background_color,
                                                'heading_color' => $this->heading_color,
                                                'body_color' => $this->body_color,
                                                'button_background' => $this->button_background,
                                                'button_text_color' => $this->button_text_color,
                                            ]);
                                        }),

                                    Forms\Components\ColorPicker::make('body_color')
                                        ->label('Body Color')
                                        ->default('#999999')
                                        ->live(onBlur: true)
                                        ->visible(fn () => in_array($this->message_type, ['center', 'full']))
                                        ->afterStateUpdated(function () {
                                            $this->dispatch('updatePreview', data: [
                                                'message_type' => $this->message_type,
                                                'message_heading' => $this->message_heading,
                                                'body_text' => $this->body_text,
                                                'button_text' => $this->button_text,
                                                'image_url' => $this->getImageUrl(),
                                                'background_color' => $this->background_color,
                                                'heading_color' => $this->heading_color,
                                                'body_color' => $this->body_color,
                                                'button_background' => $this->button_background,
                                                'button_text_color' => $this->button_text_color,
                                            ]);
                                        }),

                                    Forms\Components\ColorPicker::make('button_background')
                                        ->label('Button Background')
                                        ->default('#1f8feb')
                                        ->live(onBlur: true)
                                        ->visible(fn () => in_array($this->message_type, ['center', 'full']))
                                        ->afterStateUpdated(function () {
                                            $this->dispatch('updatePreview', data: [
                                                'message_type' => $this->message_type,
                                                'message_heading' => $this->message_heading,
                                                'body_text' => $this->body_text,
                                                'button_text' => $this->button_text,
                                                'image_url' => $this->getImageUrl(),
                                                'background_color' => $this->background_color,
                                                'heading_color' => $this->heading_color,
                                                'body_color' => $this->body_color,
                                                'button_background' => $this->button_background,
                                                'button_text_color' => $this->button_text_color,
                                            ]);
                                        }),

                                    Forms\Components\ColorPicker::make('button_text_color')
                                        ->label('Button Text Color')
                                        ->default('#ffffff')
                                        ->live(onBlur: true)
                                        ->visible(fn () => in_array($this->message_type, ['center', 'full']))
                                        ->afterStateUpdated(function () {
                                            $this->dispatch('updatePreview', data: [
                                                'message_type' => $this->message_type,
                                                'message_heading' => $this->message_heading,
                                                'body_text' => $this->body_text,
                                                'button_text' => $this->button_text,
                                                'image_url' => $this->getImageUrl(),
                                                'background_color' => $this->background_color,
                                                'heading_color' => $this->heading_color,
                                                'body_color' => $this->body_color,
                                                'button_background' => $this->button_background,
                                                'button_text_color' => $this->button_text_color,
                                            ]);
                                        }),
                                ]),
                        ])
                        ->columnSpan(1),

                    LivewireComponent::make(\App\Livewire\NotificationPreview::class)
                        ->key('notification-preview')
                        ->data(fn () => [
                            'notificationData' => [
                                'message_type' => $this->message_type,
                                'message_heading' => $this->message_heading,
                                'body_text' => $this->body_text,
                                'button_text' => $this->button_text,
                                'image_url' => $this->getImageUrl(),
                                'background_color' => $this->background_color,
                                'heading_color' => $this->heading_color,
                                'body_color' => $this->body_color,
                                'button_background' => $this->button_background,
                                'button_text_color' => $this->button_text_color,
                            ],
                        ])
                        ->extraAttributes(['style' => 'position: sticky; top: 4.5rem;'])
                        ->columnSpan(1),
                ])
                ->columns(2),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('send')
                ->label('Send Notification')
                ->action('submit')
                ->color('primary')
                ->size('lg'),
        ];
    }

    public function submit(): void
    {
        // Handle notification sending logic here
        \Filament\Notifications\Notification::make()
            ->title('Notification Sent')
            ->success()
            ->send();
    }
}
