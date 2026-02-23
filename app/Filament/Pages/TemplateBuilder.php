<?php

namespace App\Filament\Pages;

use App\Models\Template;
use App\Filament\Resources\Templates\TemplateResource;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\Auth;
use BackedEnum;
use UnitEnum;

class TemplateBuilder extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string | UnitEnum | null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Template Builder';

    protected static bool $shouldRegisterNavigation = false;

    public ?int $templateId = null;
    public ?string $templateName = null;
    public ?string $industry = null;
    public ?string $style = null;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('super_admin');
    }

    public function getView(): string
    {
        return 'filament.pages.template-builder';
    }

    public function mount(?int $template = null)
    {
        if (! $template) {
            $template = request()->integer('template');
        }

        if (! $template) {
            \Filament\Notifications\Notification::make()
                ->title('Please select a template to edit')
                ->warning()
                ->send();

            return $this->redirect(TemplateResource::getUrl());
        }

        $this->templateId = $template;
        $templateData = Template::find($template);

        if (! $templateData) {
            \Filament\Notifications\Notification::make()
                ->title('Template not found')
                ->danger()
                ->send();

            return $this->redirect(TemplateResource::getUrl());
        }

        $this->templateName = $templateData->name;
        $this->industry = $templateData->industry;
        $this->style = $templateData->style;

        $layout = [];

        if (! empty($templateData->layout_json)) {
            $decoded = json_decode($templateData->layout_json, true);
            if (is_array($decoded)) {
                $layout = $decoded;
            }
        }

        $this->form->fill([
            'layout_data' => $layout,
        ]);
    }

    public function save(): void
    {
        if (! $this->templateId) {
            Notification::make()
                ->title('No template selected')
                ->danger()
                ->send();

            return;
        }

        $template = Template::find($this->templateId);

        if (! $template) {
            Notification::make()
                ->title('Template not found')
                ->danger()
                ->send();

            return;
        }

        $state = $this->form->getState();
        $layout = $state['layout_data'] ?? [];

        if (! is_array($layout)) {
            $layout = [];
        }

        $template->update([
            'layout_json' => json_encode($layout),
        ]);

        Notification::make()
            ->title('Template saved successfully')
            ->success()
            ->send();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
                Forms\Components\Builder::make('layout_data')
                    ->label('Template layout')
                    ->hiddenLabel()
                    ->live(onBlur: false)
                    ->afterStateUpdated(function (): void {
                        $this->dispatch('preview-updated', data: $this->data['layout_data'] ?? []);
                        $this->dispatch('preview-updating');
                    })
                    ->blocks([
                        Forms\Components\Builder\Block::make('banner')
                            ->label('Banner')
                            ->schema([
                                Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('title')
                                        ->label('Badge Text')
                                        ->maxLength(255)
                                        ->default('SALE'),

                                    Forms\Components\TextInput::make('subtitle')
                                        ->label('Subtitle')
                                        ->maxLength(255)
                                        ->default('New Collection')
                                        ->hidden(),
                                ]),

                                Forms\Components\FileUpload::make('image_url')
                                    ->label('Image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('home-layout')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('cta_label')
                                    ->label('CTA label')
                                    ->maxLength(255)
                                    ->default('')
                                    ->hidden(),

                                Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('cta_target_type')
                                            ->label('CTA target type')
                                            ->options([
                                                'none' => 'None',
                                                'product' => 'Product',
                                                'brand' => 'Brand',
                                                'category' => 'Category',
                                                'url' => 'URL',
                                            ])
                                            ->default('none')
                                            ->disabled()
                                            ->hidden(),

                                        Forms\Components\TextInput::make('cta_target_id')
                                            ->label('CTA target ID')
                                            ->maxLength(255)
                                            ->disabled()
                                            ->hidden(),
                                    ]),

                                Forms\Components\TextInput::make('cta_target_url')
                                    ->label('CTA target URL')
                                    ->maxLength(2048)
                                    ->disabled()
                                    ->hidden(),
                            ]),

                        Forms\Components\Builder\Block::make('mini_banner')
                            ->label('Mini banner')
                            ->schema([
                                Forms\Components\FileUpload::make('image_url')
                                    ->label('Image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('home-layout')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('url')
                                    ->label('URL (optional)')
                                    ->maxLength(2048)
                                    ->default('')
                                    ->hidden(),
                            ]),

                        Forms\Components\Builder\Block::make('promo_card')
                            ->label('Promo card')
                            ->schema([
                                Forms\Components\FileUpload::make('image_url')
                                    ->label('Image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('home-layout')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('title')
                                    ->label('Title')
                                    ->maxLength(255)
                                    ->default('Exclusive Deals'),
                                Forms\Components\Textarea::make('text')
                                    ->label('Text')
                                    ->maxLength(1000)
                                    ->rows(3)
                                    ->default('Discover the latest styles curated for you.'),
                                Forms\Components\TextInput::make('cta_label')
                                    ->label('CTA label')
                                    ->maxLength(255)
                                    ->default('')
                                    ->hidden(),

                                Forms\Components\Select::make('cta_target_type')
                                    ->label('CTA target type')
                                    ->options([
                                        'none' => 'None',
                                        'brand' => 'Brand',
                                        'category' => 'Category',
                                    ])
                                    ->default('none')
                                    ->disabled()
                                    ->hidden(),

                                Forms\Components\TextInput::make('cta_target_id')
                                    ->label('CTA target ID')
                                    ->maxLength(255)
                                    ->disabled()
                                    ->hidden(),
                            ]),

                        Forms\Components\Builder\Block::make('category_grid')
                            ->label('Category grid')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Title')
                                    ->maxLength(255)
                                    ->default('Shop by category'),
                                Forms\Components\Repeater::make('categories')
                                    ->label('Categories')
                                    ->schema([
                                        Forms\Components\FileUpload::make('image_url')
                                            ->label('Image')
                                            ->image()
                                            ->disk('public')
                                            ->directory('home-layout')
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('title')
                                            ->label('Title')
                                            ->maxLength(255)
                                            ->default('Category'),
                                        Forms\Components\TextInput::make('category_id')
                                            ->label('Category ID (optional)')
                                            ->maxLength(255)
                                            ->default('')
                                            ->hidden(),
                                    ])
                                    ->default([
                                        ['title' => 'Women', 'image_url' => ''],
                                        ['title' => 'Men', 'image_url' => ''],
                                        ['title' => 'Kids', 'image_url' => ''],
                                        ['title' => 'Accessories', 'image_url' => ''],
                                    ])
                                    ->columns(2)
                                    ->collapsible(),
                            ]),

                        Forms\Components\Builder\Block::make('new_releases')
                            ->label('New Releases')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Title')
                                    ->maxLength(255)
                                    ->default('New Arrivals'),
                                Forms\Components\TextInput::make('limit')
                                    ->label('Number of products to show')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(50)
                                    ->default(10)
                                    ->helperText('Flutter app will fetch the latest products from Shopify')
                                    ->hidden(),
                            ]),

                        Forms\Components\Builder\Block::make('product_list')
                            ->label('Product List')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Title')
                                    ->maxLength(255)
                                    ->default('Featured products'),
                                Forms\Components\Repeater::make('products')
                                    ->label('Products (template placeholders)')
                                    ->schema([
                                        Forms\Components\TextInput::make('product_id')
                                            ->label('Product ID (optional)')
                                            ->maxLength(255)
                                            ->default('')
                                            ->hidden(),
                                    ])
                                    ->default([])
                                    ->hidden(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                SchemaActions::make($this->getFormActions())
                    ->key('form-actions'),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Save')
            ->submit('save')
            ->keyBindings(['mod+s']);
    }
}
