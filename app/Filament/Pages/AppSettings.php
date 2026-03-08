<?php

namespace App\Filament\Pages;

use App\Models\AppConfiguration;
use App\Models\Tenant;
use App\Services\CategorySyncService;
use App\Services\Shopify\ShopifyStorefrontService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use UnitEnum;

class AppSettings extends Page implements HasForms
{
    use CanUseDatabaseTransactions;
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string | UnitEnum | null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'App Settings';

    protected static ?string $title = 'App Settings';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public ?Tenant $tenant = null;

    public function mount(): void
    {
        /** @var Authenticatable|null $user */
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'hasAnyRole')) {
            abort(403);
        }

        if ($user->hasRole('super_admin')) {
            $this->redirect(route('filament.admin.resources.tenants.index'));

            return;
        }

        $this->tenant = $this->resolveTenant();

        $config = AppConfiguration::firstOrCreate(
            ['tenant_id' => $this->tenant->id],
            ['config' => []]
        );

        $this->data = $config->config ?? [];

        $this->form->fill($this->data);
    }

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

    protected function resolveTenant(): Tenant
    {
        /** @var Authenticatable $user */
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'tenant')) {
            abort(403, 'User does not belong to a tenant.');
        }

        /** @var Tenant|null $tenant */
        $tenant = $user->tenant;

        if (! $tenant) {
            abort(403, 'User does not belong to a tenant.');
        }

        return $tenant;
    }

    public function save(): void
    {
        $this->beginDatabaseTransaction();

        try {
            $data = $this->form->getState();

            if (! $this->tenant) {
                abort(403, 'Tenant not resolved.');
            }

            $config = AppConfiguration::firstOrCreate(
                ['tenant_id' => $this->tenant->id],
                ['config' => []]
            );

            $oldSyncEnabled = $config->config['basic']['sync_level_0_categories'] ?? false;
            $newSyncEnabled = $data['basic']['sync_level_0_categories'] ?? false;

            $config->config = $data;
            $config->save();

            $this->commitDatabaseTransaction();

            // Trigger category sync if checkbox was just enabled
            if (!$oldSyncEnabled && $newSyncEnabled) {
                $shopifyService = new \App\Services\Shopify\ShopifyStorefrontService();
                $syncService = new \App\Services\CategorySyncService($shopifyService);

                $result = $syncService->syncCategories($this->tenant);

                if ($result['success']) {
                    Notification::make()
                        ->title('Categories synced successfully')
                        ->body($result['message'] . ' Please assign layouts to categories.')
                        ->success()
                        ->send();

                    // Redirect to assignments page
                    $this->redirect(route('filament.admin.pages.home-layout-assignments'));
                } else {
                    Notification::make()
                        ->title('App settings saved')
                        ->body('Settings saved, but category sync failed: ' . $result['message'])
                        ->warning()
                        ->send();
                }
            } else {
                Notification::make()
                    ->title('App settings saved')
                    ->success()
                    ->send();
            }
        } catch (\Throwable $e) {
            $this->rollBackDatabaseTransaction();

            Notification::make()
                ->title('Failed to save app settings')
                ->danger()
                ->send();

            throw $e;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
            Tabs::make('Settings')
                ->tabs([
                    Tabs\Tab::make('Basic')
                        ->schema([
                            Forms\Components\TextInput::make('basic.app_name')
                                ->label('App Name')
                                ->maxLength(255)
                                ->required()
                                ->placeholder('e.g., Cela Vogue'),

                            Forms\Components\FileUpload::make('basic.logo')
                                ->label('App Logo')
                                ->image()
                                ->disk('public')
                                ->directory('app-settings')
                                ->columnSpanFull(),

                            Forms\Components\ColorPicker::make('basic.primary_color')
                                ->label('Primary Color')
                                ->placeholder('#000000'),

                            Forms\Components\ColorPicker::make('basic.secondary_color')
                                ->label('Secondary Color')
                                ->placeholder('#ffffff'),

                            Forms\Components\Checkbox::make('basic.show_brands')
                                ->label('Show Brands')
                                ->helperText('Display brands section in the app')
                                ->default(true)
                                ->columnSpanFull(),

                            Forms\Components\Placeholder::make('level_0_categories_heading')
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString('<div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;"><h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">Main Categories</h3><p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem;">Configure how main categories are displayed and synced from Shopify</p></div>'))
                                ->columnSpanFull(),

                            Forms\Components\Placeholder::make('sync_action')
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString(
                                    '<button
                                        type="button"
                                        wire:click="syncMainCategories"
                                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm fi-ac-action fi-ac-btn-action"
                                        style="background-color: #252621; color: white; border: 1px solid #252621;"
                                        onmouseover="this.style.backgroundColor=\'#3a3a35\'"
                                        onmouseout="this.style.backgroundColor=\'#252621\'"
                                    >
                                        <svg class="fi-btn-icon h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                        <span class="fi-btn-label">
                                            Sync Main Categories from Shopify
                                        </span>
                                    </button>'
                                ))
                                ->columnSpanFull(),

                            Forms\Components\Checkbox::make('basic.use_multiple_home_layouts')
                                ->label(function ($get) {
                                    $label = 'Use Multiple Home Layouts';
                                    if ($get('basic.use_multiple_home_layouts')) {
                                        $configureLink = '<a href="' . route('filament.admin.pages.home-layout-assignments') . '"
                                            style="color: #3b82f6; text-decoration: none; font-weight: 500; margin-left: 1rem; font-size: 0.875rem;"
                                            onmouseover="this.style.textDecoration=\'underline\'"
                                            onmouseout="this.style.textDecoration=\'none\'">
                                            Configure →
                                        </a>';
                                        return new \Illuminate\Support\HtmlString($label . $configureLink);
                                    }
                                    return $label;
                                })
                                ->helperText('Enable category-specific home layouts for different collections')
                                ->default(false)
                                ->live()
                                ->columnSpanFull(),

                            Forms\Components\Checkbox::make('basic.show_level_0_tabs')
                                ->label('Show Main Category Tabs')
                                ->helperText('Display main category tabs in the app')
                                ->default(true)
                                ->columnSpanFull(),

                            Forms\Components\Checkbox::make('basic.show_category_selection_screen')
                                ->label('Show Category Selection Screen')
                                ->helperText('Display category selection screen in the app')
                                ->default(true)
                                ->columnSpanFull(),
                        ]),

                    Tabs\Tab::make('Contact Info')
                        ->schema([
                            Forms\Components\TextInput::make('contact.email')
                                ->label('Email')
                                ->email()
                                ->maxLength(255)
                                ->placeholder('info@example.com'),

                            Forms\Components\TextInput::make('contact.phone')
                                ->label('Phone')
                                ->tel()
                                ->maxLength(255)
                                ->placeholder('+1234567890'),

                            Forms\Components\Textarea::make('contact.address')
                                ->label('Address')
                                ->maxLength(500)
                                ->rows(3)
                                ->placeholder('123 Fashion Street, City, Country')
                                ->columnSpanFull(),
                        ]),

                    Tabs\Tab::make('Social Media')
                        ->schema([
                            Forms\Components\TextInput::make('social_media.facebook')
                                ->label('Facebook URL')
                                ->url()
                                ->maxLength(255)
                                ->placeholder('https://facebook.com/yourpage')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('social_media.instagram')
                                ->label('Instagram URL')
                                ->url()
                                ->maxLength(255)
                                ->placeholder('https://instagram.com/yourpage')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('social_media.twitter')
                                ->label('Twitter URL')
                                ->url()
                                ->maxLength(255)
                                ->placeholder('https://twitter.com/yourpage')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('social_media.tiktok')
                                ->label('TikTok URL')
                                ->url()
                                ->maxLength(255)
                                ->placeholder('https://tiktok.com/@yourpage')
                                ->columnSpanFull(),
                        ]),

                    Tabs\Tab::make('Static Pages')
                        ->schema([
                            Forms\Components\TextInput::make('static_pages.privacy_policy')
                                ->label('Privacy Policy URL')
                                ->url()
                                ->maxLength(255)
                                ->placeholder('https://yoursite.com/privacy-policy')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('static_pages.return_policy')
                                ->label('Return Policy URL')
                                ->url()
                                ->maxLength(255)
                                ->placeholder('https://yoursite.com/return-policy')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('static_pages.shipping_policy')
                                ->label('Shipping Policy URL')
                                ->url()
                                ->maxLength(255)
                                ->placeholder('https://yoursite.com/shipping-policy')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('static_pages.payment_policy')
                                ->label('Payment Policy URL')
                                ->url()
                                ->maxLength(255)
                                ->placeholder('https://yoursite.com/payment-policy')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('static_pages.about_us')
                                ->label('About Us URL')
                                ->url()
                                ->maxLength(255)
                                ->placeholder('https://yoursite.com/about-us')
                                ->columnSpanFull(),
                        ]),

                    Tabs\Tab::make('E-commerce')
                        ->schema([
                            Forms\Components\TextInput::make('ecommerce.shipping_promise')
                                ->label('Shipping Promise')
                                ->maxLength(255)
                                ->placeholder('e.g., Free shipping on orders over $50')
                                ->helperText('Display text shown to customers about shipping')
                                ->columnSpanFull(),

                            Forms\Components\FileUpload::make('ecommerce.payment_options_image')
                                ->label('Payment Options Image')
                                ->image()
                                ->disk('public')
                                ->directory('app-settings/payment-options')
                                ->helperText('Upload an image showing accepted payment methods')
                                ->columnSpanFull(),

                            Forms\Components\Checkbox::make('ecommerce.tabby_enabled')
                                ->label('Enable Tabby')
                                ->helperText('Buy now, pay later with Tabby')
                                ->reactive()
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('ecommerce.tabby_text')
                                ->label('Tabby Display Text')
                                ->maxLength(255)
                                ->placeholder('e.g., Split in 4. No interest, no fees')
                                ->visible(fn (callable $get) => $get('ecommerce.tabby_enabled'))
                                ->columnSpanFull(),

                            Forms\Components\Checkbox::make('ecommerce.tamara_enabled')
                                ->label('Enable Tamara')
                                ->helperText('Buy now, pay later with Tamara')
                                ->reactive()
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('ecommerce.tamara_text')
                                ->label('Tamara Display Text')
                                ->maxLength(255)
                                ->placeholder('e.g., Pay in 3 easy installments')
                                ->visible(fn (callable $get) => $get('ecommerce.tamara_enabled'))
                                ->columnSpanFull(),
                        ]),

                    Tabs\Tab::make('Add-ons')
                        ->schema([
                            Forms\Components\TextInput::make('addons.whatsapp_chat_number')
                                ->label('WhatsApp Chat Number')
                                ->tel()
                                ->maxLength(255)
                                ->placeholder('+1234567890')
                                ->helperText('WhatsApp number for customer support chat')
                                ->columnSpanFull(),
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

    public function syncMainCategories(): void
    {
        $tenant = $this->resolveTenant();

        $shopifyService = new ShopifyStorefrontService();

        // First check if flutter-menu exists in Shopify
        try {
            $menu = $shopifyService->getMenuByHandle($tenant, 'flutter-menu');

            if (!$menu) {
                Notification::make()
                    ->title('Flutter Menu Not Found')
                    ->body('The "flutter-menu" is not configured in your Shopify store. Please create it first in Shopify Admin > Navigation.')
                    ->danger()
                    ->send();
                return;
            }

            if (empty($menu['items'])) {
                Notification::make()
                    ->title('Menu is Empty')
                    ->body('The "flutter-menu" exists but has no items. Please add collections to the menu in Shopify.')
                    ->warning()
                    ->send();
                return;
            }

            // Proceed with sync
            $syncService = new CategorySyncService($shopifyService);
            $result = $syncService->syncCategories($tenant);

            if ($result['success']) {
                Notification::make()
                    ->title('Categories Synced')
                    ->body($result['message'] . ' Please assign layouts to categories.')
                    ->success()
                    ->send();

                // Redirect to assignments page
                $this->redirect(route('filament.admin.pages.home-layout-assignments'));
            } else {
                Notification::make()
                    ->title('Sync Failed')
                    ->body($result['message'])
                    ->danger()
                    ->send();
            }

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Sync Error')
                ->body('Failed to connect to Shopify: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
