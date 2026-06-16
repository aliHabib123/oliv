<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Models\AppConfig;
use App\Models\AppTheme;
use App\Models\OnboardingSession;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class ViewTenantConfig extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = TenantResource::class;

    protected string $view = 'filament.resources.tenant-resource.pages.view-tenant-config';

    protected static ?string $title = 'App Configuration';

    public ?array $data = [];

    public ?Tenant $tenant = null;

    public ?AppConfig $appConfig = null;

    public ?OnboardingSession $onboardingSession = null;

    public function mount(int|string $record): void
    {
        $this->tenant = Tenant::findOrFail($record);
        $this->appConfig = AppConfig::where('tenant_id', $this->tenant->id)->first();
        $this->onboardingSession = OnboardingSession::where('tenant_id', $this->tenant->id)->latest()->first();

        $imported = $this->onboardingSession?->imported_data ?? [];

        $this->data = [
            // Branding (editable)
            'app_name' => $this->appConfig?->app_name ?? '',
            'primary_color' => $this->appConfig?->primary_color ?? '#000000',
            'secondary_color' => $this->appConfig?->secondary_color ?? '#ffffff',
            'font_family' => $this->appConfig?->font_family ?? 'Inter',
            'app_icon_url' => $this->appConfig?->app_icon_url ?? '',
            'splash_url' => $this->appConfig?->splash_url ?? '',
            // Theme & Build (editable)
            'theme_id' => $this->appConfig?->theme_id,
            'publish_status' => $this->appConfig?->publish_status ?? 'draft',
            'bundle_id_ios' => $this->appConfig?->bundle_id_ios ?? '',
            'bundle_id_android' => $this->appConfig?->bundle_id_android ?? '',
            // Imported data (read-only)
            'store_name' => $imported['store_info']['name'] ?? '',
            'domain' => $this->onboardingSession?->shopify_domain ?? '',
            'currency' => $imported['store_info']['currency'] ?? '',
            'country' => $imported['store_info']['country'] ?? '',
            'products' => (string) ($imported['catalog']['product_count'] ?? 0),
            'collections' => (string) ($imported['catalog']['category_count'] ?? 0),
            'imported_at' => ! empty($imported['imported_at']) ? \Carbon\Carbon::parse($imported['imported_at'])->format('M d, Y H:i') : '',
            'completed_at' => $this->onboardingSession?->completed_at?->format('M d, Y H:i') ?? 'In progress',
        ];

        $this->form->fill($this->data);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (! $this->appConfig) {
            $this->appConfig = new AppConfig(['tenant_id' => $this->tenant->id]);
        }

        $this->appConfig->fill([
            'app_name' => $data['app_name'],
            'primary_color' => $data['primary_color'],
            'secondary_color' => $data['secondary_color'],
            'font_family' => $data['font_family'],
            'app_icon_url' => $data['app_icon_url'],
            'splash_url' => $data['splash_url'],
            'theme_id' => $data['theme_id'],
            'publish_status' => $data['publish_status'],
            'bundle_id_ios' => $data['bundle_id_ios'],
            'bundle_id_android' => $data['bundle_id_android'],
        ]);
        $this->appConfig->save();

        Notification::make()
            ->title('Configuration saved')
            ->success()
            ->send();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
                Tabs::make('Config')
                    ->tabs([
                        Tabs\Tab::make('Branding')
                            ->schema([
                                Forms\Components\TextInput::make('app_name')
                                    ->label('App Name')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\ViewField::make('app_icon_preview')
                                    ->label('App Icon')
                                    ->view('filament.forms.app-icon-preview')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('app_icon_url')
                                    ->label('App Icon URL')
                                    ->url()
                                    ->maxLength(2048)
                                    ->columnSpanFull(),

                                Forms\Components\ColorPicker::make('primary_color')
                                    ->label('Primary Color')
                                    ->required(),

                                Forms\Components\ColorPicker::make('secondary_color')
                                    ->label('Secondary Color')
                                    ->required(),

                                Forms\Components\Select::make('font_family')
                                    ->label('Font Family')
                                    ->options([
                                        'Inter' => 'Inter',
                                        'Roboto' => 'Roboto',
                                        'Poppins' => 'Poppins',
                                        'Montserrat' => 'Montserrat',
                                        'Open Sans' => 'Open Sans',
                                        'Playfair Display' => 'Playfair Display',
                                    ]),

                                Forms\Components\TextInput::make('splash_url')
                                    ->label('Splash Screen URL')
                                    ->url()
                                    ->maxLength(2048)
                                    ->columnSpanFull(),
                            ]),

                        Tabs\Tab::make('Theme & Build')
                            ->schema([
                                Forms\Components\Select::make('theme_id')
                                    ->label('Theme')
                                    ->options(AppTheme::pluck('name', 'id'))
                                    ->placeholder('Select a theme'),

                                Forms\Components\Select::make('publish_status')
                                    ->label('Publish Status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'building' => 'Building',
                                        'submitted' => 'Submitted',
                                        'approved' => 'Approved',
                                        'live' => 'Live',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->required(),

                                Forms\Components\TextInput::make('bundle_id_ios')
                                    ->label('Bundle ID (iOS)')
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('bundle_id_android')
                                    ->label('Bundle ID (Android)')
                                    ->maxLength(255)
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

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Configuration')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }
}
