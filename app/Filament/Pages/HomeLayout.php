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
use Filament\Schemas\Components\Livewire as LivewireComponent;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use UnitEnum;

class HomeLayout extends Page
{
    use CanUseDatabaseTransactions;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string | UnitEnum | null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Home layout';

    protected static ?int $navigationSort = 10;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected ?Tenant $tenant = null;

    public ?int $selectedLayoutId = null;

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

        // Try to load from home_layouts table first
        $homeLayout = null;

        if ($this->selectedLayoutId) {
            $homeLayout = \App\Models\HomeLayout::where('tenant_id', $tenant->id)
                ->where('id', $this->selectedLayoutId)
                ->first();
        }

        // If no specific layout selected, load default or first available
        if (!$homeLayout) {
            $homeLayout = \App\Models\HomeLayout::where('tenant_id', $tenant->id)
                ->where('is_default', true)
                ->first();
        }

        // Set the selected layout ID
        if ($homeLayout) {
            $this->selectedLayoutId = $homeLayout->id;
        }

        $data = [
            'is_default' => $homeLayout?->is_default ?? false,
            'home_layout' => $homeLayout?->layout_data ?? $tenant->home_layout ?? [],
        ];

        $this->form->fill($data);
    }

    public function save(): void
    {
        $tenant = $this->resolveTenant();

        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            // Save to home_layouts table if we have a selected layout
            if ($this->selectedLayoutId) {
                $homeLayout = \App\Models\HomeLayout::where('tenant_id', $tenant->id)
                    ->where('id', $this->selectedLayoutId)
                    ->first();

                if ($homeLayout) {
                    $homeLayout->update([
                        'layout_data' => $data['home_layout'] ?? [],
                    ]);
                }
            } else {
                // Fallback to old tenant.home_layout for backward compatibility
                $tenant->update([
                    'home_layout' => $data['home_layout'] ?? [],
                ]);
            }

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


    protected function getSelectedLayoutLabel(): string
    {
        if (!$this->selectedLayoutId) {
            return 'Select Layout';
        }

        $layout = \App\Models\HomeLayout::find($this->selectedLayoutId);

        if (!$layout) {
            return 'Select Layout';
        }

        $label = $layout->name;

        if ($layout->is_default) {
            $label .= ' (Default)';
        } else if ($layout->category_title) {
            $label = $layout->category_title;
        }

        return $label;
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

        // Get available layouts
        $layoutOptions = $this->getLayoutOptions();

        return $schema->schema([
            Forms\Components\Builder::make('home_layout')
                ->label('Home layout blocks')
                ->hiddenLabel()
                ->live(onBlur: false)
                ->afterStateUpdated(function () {
                    $this->dispatch('preview-updated', data: $this->data['home_layout'] ?? []);
                    $this->dispatch('preview-updating');
                })
                ->collapseAllAction(
                    fn (Action $action) => $action
                        ->label('')
                        ->icon('heroicon-o-chevron-up')
                        ->tooltip('Collapse all blocks')
                )
                ->expandAllAction(
                    fn (Action $action) => $action
                        ->label('')
                        ->icon('heroicon-o-chevron-down')
                        ->tooltip('Expand all blocks')
                )
                ->blocks([
                    Block::make('banner')
                        ->label('Banner')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('title')
                                        ->label('Badge Text')
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('subtitle')
                                        ->label('Subtitle')
                                        ->maxLength(255)
                                        ->hidden(),
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
                                        ->maxLength(255)
                                        ->hidden(),

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

    protected function getLayoutOptions(): array
    {
        $tenant = $this->resolveTenant();

        $layouts = \App\Models\HomeLayout::where('tenant_id', $tenant->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('category_title')
            ->get();

        $options = [];

        foreach ($layouts as $layout) {
            $label = $layout->name;

            if ($layout->is_default) {
                $label .= ' (Default)';
            } else if ($layout->category_title) {
                $label .= ' - ' . $layout->category_title;
            }

            $options[$layout->id] = $label;
        }

        return $options;
    }

    public function content(Schema $schema): Schema
    {
        $layoutOptions = $this->getLayoutOptions();

        return $schema
            ->components([
                // Full-width row for layout selector in white panel
                Forms\Components\Placeholder::make('layout_selector_panel')
                    ->hiddenLabel()
                    ->content(fn() => new \Illuminate\Support\HtmlString(
                        '<div style="background: white; border: 1px solid rgb(229, 231, 235); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                            <div style="display: flex; align-items: flex-start; gap: 2rem;">
                                <div style="flex: 0 0 400px;">
                                    <label style="display: block; font-size: 0.875rem; font-weight: 500; color: rgb(55, 65, 81); margin-bottom: 0.5rem;">Select Layout</label>
                                    <div style="display: flex; gap: 0.5rem; align-items: start;">
                                        <select
                                            wire:model="selectedLayoutId"
                                            style="flex: 1; border: 1px solid rgb(209, 213, 219); border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; background: white; color: rgb(17, 24, 39);"
                                        >
                                            ' . collect($layoutOptions)->map(function($label, $id) {
                                                $selected = $this->selectedLayoutId == $id ? 'selected' : '';
                                                return '<option value="' . $id . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
                                            })->join('') . '
                                        </select>
                                        <button
                                            type="button"
                                            wire:click="handleLayoutChange"
                                            wire:loading.attr="disabled"
                                            style="background-color: #252621; color: white; border: 1px solid #252621; border-radius: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; white-space: nowrap; cursor: pointer; transition: background-color 0.2s; display: flex; align-items: center; gap: 0.5rem;"
                                            onmouseover="this.style.backgroundColor=\'#3a3a35\'"
                                            onmouseout="this.style.backgroundColor=\'#252621\'"
                                        >
                                            <svg wire:loading wire:target="handleLayoutChange" style="animation: spin 1s linear infinite; width: 1rem; height: 1rem;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span wire:loading.remove wire:target="handleLayoutChange">Switch</span>
                                            <span wire:loading wire:target="handleLayoutChange">Switching...</span>
                                        </button>
                                        <style>
                                            @keyframes spin {
                                                from { transform: rotate(0deg); }
                                                to { transform: rotate(360deg); }
                                            }
                                        </style>
                                    </div>
                                    <p style="font-size: 0.75rem; color: rgb(107, 114, 128); margin-top: 0.25rem;">Choose which layout to edit</p>
                                </div>

                                <div style="flex: 1; display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: rgb(249, 250, 251); border-radius: 0.5rem;margin-top:1rem;">
                                    <input
                                        type="checkbox"
                                        ' . (($this->data['is_default'] ?? false) ? 'checked' : '') . '
                                        disabled
                                        style="width: 1rem; height: 1rem; border: 1px solid rgb(209, 213, 219); border-radius: 0.25rem; flex-shrink: 0;"
                                    >
                                    <div>
                                        <span style="font-weight: 500; color: rgb(55, 65, 81); font-size: 0.875rem;">Default Layout</span>
                                        <p style="font-size: 0.75rem; color: rgb(107, 114, 128); margin: 0;">Default layout is used as fallback when no category-specific layout is found</p>
                                    </div>
                                </div>
                            </div>
                        </div>'
                    )),

                // Two-column layout: builder on left, preview on right
                Grid::make(2)
                    ->schema([
                        // Left column: Form builder
                        $this->getFormContentComponent(),

                        // Right column: Live preview with sticky positioning to stay on screen during scroll
                        LivewireComponent::make(\App\Livewire\HomeLayoutPreview::class)
                            ->key('home-layout-preview')
                            ->data([
                                'layoutData' => $this->data['home_layout'] ?? [],
                                'tenant' => $this->tenant,
                            ])
                            ->extraAttributes(['style' => 'position: sticky; top: 4.5rem; margin-top: 2.2rem;']),
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

    public function handleLayoutChange(): void
    {
        $this->fillForm();

        // Update the preview with the new layout data
        $this->dispatch('preview-updated', data: $this->data['home_layout'] ?? []);
        $this->dispatch('preview-updating');

        Notification::make()
            ->title('Layout Switched')
            ->body('Successfully switched to the selected layout')
            ->success()
            ->send();
    }
}
