<?php

namespace App\Filament\Pages;

use App\Models\AppConfiguration;
use App\Models\Tenant;
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

    protected static string | UnitEnum | null $navigationGroup = 'App Settings';

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

            $config->config = $data;
            $config->save();

            $this->commitDatabaseTransaction();

            Notification::make()
                ->title('App settings saved')
                ->success()
                ->send();
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
