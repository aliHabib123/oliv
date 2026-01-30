<?php

namespace App\Filament\Pages;

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
    public ?string $background_color = '#FFFFFF';
    public ?string $heading_color = '#222222';
    public ?string $body_color = '#999999';
    public ?string $button_background = '#1f8feb';
    public ?string $button_text_color = '#ffffff';

    public function mount(): void
    {
        $this->form->fill();
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
                                        'image_url' => $this->image_url,
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
                                        'image_url' => $this->image_url,
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
                                ->afterStateUpdated(function () {
                                    $this->dispatch('updatePreview', data: [
                                        'message_type' => $this->message_type,
                                        'message_heading' => $this->message_heading,
                                        'body_text' => $this->body_text,
                                        'button_text' => $this->button_text,
                                        'image_url' => $this->image_url,
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
                                ->afterStateUpdated(function () {
                                    $this->dispatch('updatePreview', data: [
                                        'message_type' => $this->message_type,
                                        'message_heading' => $this->message_heading,
                                        'body_text' => $this->body_text,
                                        'button_text' => $this->button_text,
                                        'image_url' => $this->image_url,
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
                                ->afterStateUpdated(function () {
                                    $this->dispatch('updatePreview', data: [
                                        'message_type' => $this->message_type,
                                        'message_heading' => $this->message_heading,
                                        'body_text' => $this->body_text,
                                        'button_text' => $this->button_text,
                                        'image_url' => $this->image_url,
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
                                        ->default('#FFFFFF')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function () {
                                            $this->dispatch('updatePreview', data: [
                                                'message_type' => $this->message_type,
                                                'message_heading' => $this->message_heading,
                                                'body_text' => $this->body_text,
                                                'button_text' => $this->button_text,
                                                'image_url' => $this->image_url,
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
                                                'image_url' => $this->image_url,
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
                                        ->afterStateUpdated(function () {
                                            $this->dispatch('updatePreview', data: [
                                                'message_type' => $this->message_type,
                                                'message_heading' => $this->message_heading,
                                                'body_text' => $this->body_text,
                                                'button_text' => $this->button_text,
                                                'image_url' => $this->image_url,
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
                                        ->afterStateUpdated(function () {
                                            $this->dispatch('updatePreview', data: [
                                                'message_type' => $this->message_type,
                                                'message_heading' => $this->message_heading,
                                                'body_text' => $this->body_text,
                                                'button_text' => $this->button_text,
                                                'image_url' => $this->image_url,
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
                                        ->afterStateUpdated(function () {
                                            $this->dispatch('updatePreview', data: [
                                                'message_type' => $this->message_type,
                                                'message_heading' => $this->message_heading,
                                                'body_text' => $this->body_text,
                                                'button_text' => $this->button_text,
                                                'image_url' => $this->image_url,
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
                                'image_url' => $this->image_url,
                                'background_color' => $this->background_color,
                                'heading_color' => $this->heading_color,
                                'body_color' => $this->body_color,
                                'button_background' => $this->button_background,
                                'button_text_color' => $this->button_text_color,
                            ],
                        ])
                        ->extraAttributes(['style' => 'position: sticky; top: 4.5rem; margin-top: 2.2rem;'])
                        ->columnSpan(1),
                ])
                ->columns(2),
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
