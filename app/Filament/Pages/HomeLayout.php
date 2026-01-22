<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
use App\Services\Shopify\ShopifyStorefrontService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Builder\Block;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use UnitEnum;

class HomeLayout extends Page
{
    use CanUseDatabaseTransactions;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string | UnitEnum | null $navigationGroup = 'App Settings';

    protected static ?string $navigationLabel = 'Home layout';

    protected static ?int $navigationSort = 10;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected ?Tenant $tenant = null;

    public static function canAccess(): bool
    {
        /** @var Authenticatable|null $user */
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole(['super_admin', 'owner', 'admin']);
        }

        return false;
    }

    public function mount(): void
    {
        /** @var Authenticatable|null $user */
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'hasAnyRole')) {
            abort(403);
        }

        // Super admin: use the existing Tenants resource to choose which tenant's layout to edit.
        if ($user->hasRole('super_admin')) {
            $this->redirect(route('filament.admin.resources.tenants.index'));

            return;
        }

        $this->tenant = $this->resolveTenant();

        $this->fillForm();
    }

    protected function resolveTenant(): Tenant
    {
        if ($this->tenant instanceof Tenant) {
            return $this->tenant;
        }

        /** @var Authenticatable|null $user */
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'hasAnyRole')) {
            abort(403);
        }

        $tenantId = $user->tenant_id ?? null;

        if (! $tenantId) {
            abort(404);
        }

        /** @var Tenant $tenant */
        $tenant = Tenant::query()->findOrFail($tenantId);

        $this->tenant = $tenant;

        return $tenant;
    }

    protected function fillForm(): void
    {
        $tenant = $this->resolveTenant();

        $data = [
            'home_layout' => $tenant->home_layout ?? [],
        ];

        $this->form->fill($data);
    }

    public function save(): void
    {
        $tenant = $this->resolveTenant();

        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            $tenant->update([
                'home_layout' => $data['home_layout'] ?? [],
            ]);

            $this->commitDatabaseTransaction();
        } catch (\Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        Notification::make()
            ->success()
            ->title('Home layout saved')
            ->send();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->model($this->resolveTenant())
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $tenant = $this->resolveTenant();

        return $schema->schema([
            Forms\Components\Builder::make('home_layout')
                ->label('Home layout blocks')
                ->blocks([
                    Block::make('banner')
                        ->label('Banner')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('title')
                                        ->label('Title')
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('subtitle')
                                        ->label('Subtitle')
                                        ->maxLength(255),
                                ]),

                            Forms\Components\FileUpload::make('image_url')
                                ->label('Image')
                                ->image()
                                ->disk('public')
                                ->directory('home-layout')
                                ->columnSpanFull(),

                            Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('cta_label')
                                        ->label('CTA label')
                                        ->maxLength(255),

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
                                        ->live(),
                                ]),

                            Forms\Components\Select::make('cta_target_product_id')
                                ->label('Product')
                                ->searchable()
                                ->afterStateHydrated(function (callable $set, callable $get, $state): void {
                                    if ($get('cta_target_type') !== 'product') {
                                        return;
                                    }

                                    // If this field already has state (e.g. after a Livewire update),
                                    // don't overwrite it.
                                    if (filled($state)) {
                                        return;
                                    }

                                    $targetId = $get('cta_target_id');

                                    if (! $targetId) {
                                        return;
                                    }

                                    // Mirror the saved canonical target ID into the product select so
                                    // the previously chosen product appears selected on load.
                                    $set('cta_target_product_id', $targetId);
                                })
                                ->getSearchResultsUsing(function (Forms\Components\Select $component, ?string $search) use ($tenant): array {
                                    $search = trim((string) $search);

                                    if ($search === '') {
                                        return [];
                                    }

                                    /** @var Tenant $tenant */
                                    /** @var ShopifyStorefrontService $shopify */
                                    $shopify = app(ShopifyStorefrontService::class);

                                    $products = $shopify->searchProducts($tenant, $search, 10);

                                    $options = [];

                                    foreach ($products as $product) {
                                        $id = $product['id'] ?? null;

                                        if (! $id) {
                                            continue;
                                        }

                                        $label = $product['title'] ?? '';

                                        if (! empty($product['vendor'])) {
                                            $label .= ' - ' . $product['vendor'];
                                        }

                                        $options[$id] = $label;
                                    }

                                    return $options;
                                })
                                ->getOptionLabelUsing(function ($value) use ($tenant): string {
                                    // Filament may pass either the raw state value or a closure
                                    // that returns the state. Support both.
                                    if (is_callable($value)) {
                                        $value = $value();
                                    }

                                    $productId = (string) $value;

                                    if (! $productId) {
                                        return '';
                                    }

                                    // If it looks like a Shopify GID, fetch the product details
                                    // to show a nice label instead of the raw ID.
                                    if (str_starts_with($productId, 'gid://shopify/Product/')) {
                                        /** @var Tenant $tenant */
                                        /** @var ShopifyStorefrontService $shopify */
                                        $shopify = app(ShopifyStorefrontService::class);

                                        $product = $shopify->getProductById($tenant, $productId);

                                        if ($product) {
                                            $label = $product['title'] ?? '';

                                            if (! empty($product['vendor'])) {
                                                $label .= ' - ' . $product['vendor'];
                                            }

                                            return $label;
                                        }
                                    }

                                    return $productId;
                                })
                                ->afterStateUpdated(function ($state, callable $set): void {
                                    // Keep canonical target ID in cta_target_id so the API and app only
                                    // need to deal with a single ID field.
                                    $set('cta_target_id', $state);
                                })
                                ->dehydrated(false)
                                ->visible(fn (callable $get): bool => $get('cta_target_type') === 'product'),

                            Forms\Components\Select::make('cta_target_category_id')
                                ->label('Category')
                                ->searchable()
                                ->afterStateHydrated(function (callable $set, callable $get, $state): void {
                                    if ($get('cta_target_type') !== 'category') {
                                        return;
                                    }

                                    if (filled($state)) {
                                        return;
                                    }

                                    $targetId = $get('cta_target_id');

                                    if (! $targetId) {
                                        return;
                                    }

                                    $set('cta_target_category_id', $targetId);
                                })
                                ->getSearchResultsUsing(function (Forms\Components\Select $component, ?string $search) use ($tenant): array {
                                    $search = trim((string) $search);

                                    if ($search === '') {
                                        return [];
                                    }

                                    /** @var Tenant $tenant */
                                    /** @var ShopifyStorefrontService $shopify */
                                    $shopify = app(ShopifyStorefrontService::class);

                                    $collections = $shopify->searchCollections($tenant, $search, 10);

                                    $options = [];

                                    foreach ($collections as $collection) {
                                        $id = $collection['id'] ?? null;

                                        if (! $id) {
                                            continue;
                                        }

                                        $options[$id] = $collection['title'] ?? '';
                                    }

                                    return $options;
                                })
                                ->getOptionLabelUsing(function ($value) use ($tenant): string {
                                    if (is_callable($value)) {
                                        $value = $value();
                                    }

                                    $collectionId = (string) $value;

                                    if (! $collectionId) {
                                        return '';
                                    }

                                    if (str_starts_with($collectionId, 'gid://shopify/Collection/')) {
                                        /** @var Tenant $tenant */
                                        /** @var ShopifyStorefrontService $shopify */
                                        $shopify = app(ShopifyStorefrontService::class);

                                        $collection = $shopify->getCollectionById($tenant, $collectionId);

                                        if ($collection) {
                                            return $collection['title'] ?? '';
                                        }
                                    }

                                    return $collectionId;
                                })
                                ->afterStateUpdated(function ($state, callable $set): void {
                                    $set('cta_target_id', $state);
                                })
                                ->dehydrated(false)
                                ->visible(fn (callable $get): bool => $get('cta_target_type') === 'category'),

                            Forms\Components\Select::make('cta_target_brand_id')
                                ->label('Brand')
                                ->searchable()
                                ->afterStateHydrated(function (callable $set, callable $get, $state): void {
                                    if ($get('cta_target_type') !== 'brand') {
                                        return;
                                    }

                                    if (filled($state)) {
                                        return;
                                    }

                                    $targetId = $get('cta_target_id');

                                    if (! $targetId) {
                                        return;
                                    }

                                    $set('cta_target_brand_id', $targetId);
                                })
                                ->getSearchResultsUsing(function (Forms\Components\Select $component, ?string $search) use ($tenant): array {
                                    $search = trim((string) $search);

                                    if ($search === '') {
                                        return [];
                                    }

                                    /** @var Tenant $tenant */
                                    /** @var ShopifyStorefrontService $shopify */
                                    $shopify = app(ShopifyStorefrontService::class);

                                    $brands = $shopify->searchBrands($tenant, $search, 10);

                                    $options = [];

                                    foreach ($brands as $brand) {
                                        $name = $brand['name'] ?? null;

                                        if (! $name) {
                                            continue;
                                        }

                                        $options[$name] = $name;
                                    }

                                    return $options;
                                })
                                ->getOptionLabelUsing(function ($value): string {
                                    if (is_callable($value)) {
                                        $value = $value();
                                    }

                                    return (string) $value;
                                })
                                ->afterStateUpdated(function ($state, callable $set): void {
                                    $set('cta_target_id', $state);
                                })
                                ->dehydrated(false)
                                ->visible(fn (callable $get): bool => $get('cta_target_type') === 'brand'),

                            Forms\Components\TextInput::make('cta_target_id')
                                ->label('CTA target ID')
                                ->maxLength(255)
                                ->dehydratedWhenHidden()
                                ->hidden(),

                            Forms\Components\TextInput::make('cta_target_url')
                                ->label('CTA target URL')
                                ->maxLength(2048)
                                ->visible(fn (callable $get): bool => $get('cta_target_type') === 'url'),
                        ]),

                    Block::make('mini_banner')
                        ->label('Mini banner')
                        ->schema([
                            Forms\Components\FileUpload::make('image_url')
                                ->label('Image')
                                ->image()
                                ->disk('public')
                                ->directory('home-layout')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('url')
                                ->label('URL')
                                ->maxLength(2048),
                        ]),

                    Block::make('promo_card')
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
                                ->required(),

                            Forms\Components\Textarea::make('text')
                                ->label('Text')
                                ->maxLength(1000)
                                ->rows(3),

                            Forms\Components\TextInput::make('cta_label')
                                ->label('CTA label')
                                ->maxLength(255),

                            Forms\Components\Select::make('cta_target_type')
                                ->label('CTA target type')
                                ->options([
                                    'none' => 'None',
                                    'brand' => 'Brand',
                                    'category' => 'Category',
                                ])
                                ->default('none')
                                ->live(),

                            Forms\Components\Select::make('cta_target_category_id')
                                ->label('Category')
                                ->searchable()
                                ->afterStateHydrated(function (callable $set, callable $get, $state): void {
                                    if ($get('cta_target_type') !== 'category') {
                                        return;
                                    }

                                    if (filled($state)) {
                                        return;
                                    }

                                    $targetId = $get('cta_target_id');

                                    if (! $targetId) {
                                        return;
                                    }

                                    $set('cta_target_category_id', $targetId);
                                })
                                ->getSearchResultsUsing(function (Forms\Components\Select $component, ?string $search) use ($tenant): array {
                                    $search = trim((string) $search);

                                    if ($search === '') {
                                        return [];
                                    }

                                    /** @var Tenant $tenant */
                                    /** @var ShopifyStorefrontService $shopify */
                                    $shopify = app(ShopifyStorefrontService::class);

                                    $collections = $shopify->searchCollections($tenant, $search, 10);

                                    $options = [];

                                    foreach ($collections as $collection) {
                                        $id = $collection['id'] ?? null;

                                        if (! $id) {
                                            continue;
                                        }

                                        $options[$id] = $collection['title'] ?? '';
                                    }

                                    return $options;
                                })
                                ->getOptionLabelUsing(function ($value) use ($tenant): string {
                                    if (is_callable($value)) {
                                        $value = $value();
                                    }

                                    $collectionId = (string) $value;

                                    if (! $collectionId) {
                                        return '';
                                    }

                                    if (str_starts_with($collectionId, 'gid://shopify/Collection/')) {
                                        /** @var Tenant $tenant */
                                        /** @var ShopifyStorefrontService $shopify */
                                        $shopify = app(ShopifyStorefrontService::class);

                                        $collection = $shopify->getCollectionById($tenant, $collectionId);

                                        if ($collection) {
                                            return $collection['title'] ?? '';
                                        }
                                    }

                                    return $collectionId;
                                })
                                ->afterStateUpdated(function ($state, callable $set): void {
                                    $set('cta_target_id', $state);
                                })
                                ->dehydrated(false)
                                ->visible(fn (callable $get): bool => $get('cta_target_type') === 'category'),

                            Forms\Components\Select::make('cta_target_brand_id')
                                ->label('Brand')
                                ->searchable()
                                ->afterStateHydrated(function (callable $set, callable $get, $state): void {
                                    if ($get('cta_target_type') !== 'brand') {
                                        return;
                                    }

                                    if (filled($state)) {
                                        return;
                                    }

                                    $targetId = $get('cta_target_id');

                                    if (! $targetId) {
                                        return;
                                    }

                                    $set('cta_target_brand_id', $targetId);
                                })
                                ->getSearchResultsUsing(function (Forms\Components\Select $component, ?string $search) use ($tenant): array {
                                    $search = trim((string) $search);

                                    if ($search === '') {
                                        return [];
                                    }

                                    /** @var Tenant $tenant */
                                    /** @var ShopifyStorefrontService $shopify */
                                    $shopify = app(ShopifyStorefrontService::class);

                                    $brands = $shopify->searchBrands($tenant, $search, 10);

                                    $options = [];

                                    foreach ($brands as $brand) {
                                        $name = $brand['name'] ?? null;

                                        if (! $name) {
                                            continue;
                                        }

                                        $options[$name] = $name;
                                    }

                                    return $options;
                                })
                                ->getOptionLabelUsing(function ($value): string {
                                    if (is_callable($value)) {
                                        $value = $value();
                                    }

                                    return (string) $value;
                                })
                                ->afterStateUpdated(function ($state, callable $set): void {
                                    $set('cta_target_id', $state);
                                })
                                ->dehydrated(false)
                                ->visible(fn (callable $get): bool => $get('cta_target_type') === 'brand'),

                            Forms\Components\TextInput::make('cta_target_id')
                                ->label('CTA target ID')
                                ->maxLength(255)
                                ->dehydratedWhenHidden()
                                ->hidden(),
                        ]),

                    Block::make('category_grid')
                        ->label('Category grid')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Title')
                                ->maxLength(255),

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
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('category_select_id')
                                        ->label('Category')
                                        ->searchable()
                                        ->required()
                                        ->afterStateHydrated(function (callable $set, callable $get, $state): void {
                                            if (filled($state)) {
                                                return;
                                            }

                                            $categoryId = $get('category_id');

                                            if (! $categoryId) {
                                                return;
                                            }

                                            $set('category_select_id', $categoryId);
                                        })
                                        ->getSearchResultsUsing(function (Forms\Components\Select $component, ?string $search) use ($tenant): array {
                                            $search = trim((string) $search);

                                            if ($search === '') {
                                                return [];
                                            }

                                            /** @var Tenant $tenant */
                                            /** @var ShopifyStorefrontService $shopify */
                                            $shopify = app(ShopifyStorefrontService::class);

                                            $collections = $shopify->searchCollections($tenant, $search, 10);

                                            $options = [];

                                            foreach ($collections as $collection) {
                                                $id = $collection['id'] ?? null;

                                                if (! $id) {
                                                    continue;
                                                }

                                                $options[$id] = $collection['title'] ?? '';
                                            }

                                            return $options;
                                        })
                                        ->getOptionLabelUsing(function ($value) use ($tenant): string {
                                            if (is_callable($value)) {
                                                $value = $value();
                                            }

                                            $collectionId = (string) $value;

                                            if (! $collectionId) {
                                                return '';
                                            }

                                            if (str_starts_with($collectionId, 'gid://shopify/Collection/')) {
                                                /** @var Tenant $tenant */
                                                /** @var ShopifyStorefrontService $shopify */
                                                $shopify = app(ShopifyStorefrontService::class);

                                                $collection = $shopify->getCollectionById($tenant, $collectionId);

                                                if ($collection) {
                                                    return $collection['title'] ?? '';
                                                }
                                            }

                                            return $collectionId;
                                        })
                                        ->afterStateUpdated(function ($state, callable $set): void {
                                            $set('category_id', $state);
                                        })
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('category_id')
                                        ->label('Category ID')
                                        ->maxLength(255)
                                        ->dehydratedWhenHidden()
                                        ->hidden(),
                                ])
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['category_id'] ?? null)
                                ->addActionLabel('Add category')
                                ->defaultItems(0)
                                ->columnSpanFull(),
                        ]),

                    Block::make('new_releases')
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
                                ->helperText('Flutter app will fetch the latest products from Shopify'),
                        ]),

                    Block::make('product_list')
                        ->label('Product List')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Title')
                                ->maxLength(255),

                            Forms\Components\Repeater::make('products')
                                ->label('Products')
                                ->schema([
                                    Forms\Components\Select::make('product_select_id')
                                        ->label('Product')
                                        ->searchable()
                                        ->required()
                                        ->afterStateHydrated(function (callable $set, callable $get, $state): void {
                                            if (filled($state)) {
                                                return;
                                            }

                                            $productId = $get('product_id');

                                            if (! $productId) {
                                                return;
                                            }

                                            $set('product_select_id', $productId);
                                        })
                                        ->getSearchResultsUsing(function (Forms\Components\Select $component, ?string $search) use ($tenant): array {
                                            $search = trim((string) $search);

                                            if ($search === '') {
                                                return [];
                                            }

                                            /** @var Tenant $tenant */
                                            /** @var ShopifyStorefrontService $shopify */
                                            $shopify = app(ShopifyStorefrontService::class);

                                            $products = $shopify->searchProducts($tenant, $search, 10);

                                            $options = [];

                                            foreach ($products as $product) {
                                                $id = $product['id'] ?? null;

                                                if (! $id) {
                                                    continue;
                                                }

                                                $label = $product['title'] ?? '';

                                                if (! empty($product['vendor'])) {
                                                    $label .= ' - ' . $product['vendor'];
                                                }

                                                $options[$id] = $label;
                                            }

                                            return $options;
                                        })
                                        ->getOptionLabelUsing(function ($value) use ($tenant): string {
                                            if (is_callable($value)) {
                                                $value = $value();
                                            }

                                            $productId = (string) $value;

                                            if (! $productId) {
                                                return '';
                                            }

                                            if (str_starts_with($productId, 'gid://shopify/Product/')) {
                                                /** @var Tenant $tenant */
                                                /** @var ShopifyStorefrontService $shopify */
                                                $shopify = app(ShopifyStorefrontService::class);

                                                $product = $shopify->getProductById($tenant, $productId);

                                                if ($product) {
                                                    $label = $product['title'] ?? '';

                                                    if (! empty($product['vendor'])) {
                                                        $label .= ' - ' . $product['vendor'];
                                                    }

                                                    return $label;
                                                }
                                            }

                                            return $productId;
                                        })
                                        ->afterStateUpdated(function ($state, callable $set): void {
                                            $set('product_id', $state);
                                        })
                                        ->dehydrated(false)
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('product_id')
                                        ->label('Product ID')
                                        ->maxLength(255)
                                        ->dehydratedWhenHidden()
                                        ->hidden(),
                                ])
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['product_id'] ?? null)
                                ->addActionLabel('Add product')
                                ->defaultItems(0)
                                ->reorderable()
                                ->columnSpanFull(),
                        ]),
                ])
                ->collapsible()
                ->columnSpanFull(),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        // Left column: Form builder
                        $this->getFormContentComponent(),

                        // Right column: Live preview
                        View::make('filament.components.home-layout-preview')
                            ->viewData([
                                'layoutData' => $this->data['home_layout'] ?? [],
                                'tenant' => $this->tenant,
                            ]),
                    ])
                    ->columns(2),
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
