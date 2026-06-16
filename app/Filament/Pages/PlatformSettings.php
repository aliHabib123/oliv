<?php

namespace App\Filament\Pages;

use App\Models\PlatformSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Crypt;
use UnitEnum;

class PlatformSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Platform Settings';

    protected static ?string $title = 'Platform Settings';

    protected static ?int $navigationSort = 0;

    public ?array $data = [];

    public function mount(): void
    {
        $this->data = [
            'shopify_app_client_id' => PlatformSetting::getValue('shopify_app_client_id', ''),
            'shopify_app_client_secret' => '', // Never pre-fill secrets
            'shopify_app_redirect_uri' => PlatformSetting::getValue('shopify_app_redirect_uri', url('/onboarding/shopify/callback')),
            'shopify_app_scopes' => PlatformSetting::getValue('shopify_app_scopes', 'read_products,read_themes,read_content,read_orders,read_customers'),
        ];

        $this->form->fill($this->data);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && method_exists($user, 'hasRole') && $user->hasRole('super_admin');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        PlatformSetting::setValue('shopify_app_client_id', $data['shopify_app_client_id'] ?? null);
        PlatformSetting::setValue('shopify_app_redirect_uri', $data['shopify_app_redirect_uri'] ?? null);
        PlatformSetting::setValue('shopify_app_scopes', $data['shopify_app_scopes'] ?? null);

        // Only update secret if a new one was provided
        if (! empty($data['shopify_app_client_secret'])) {
            PlatformSetting::setEncryptedValue('shopify_app_client_secret', $data['shopify_app_client_secret']);
        }

        Notification::make()
            ->title('Platform settings saved')
            ->success()
            ->send();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
                Tabs::make('Platform')
                    ->tabs([
                        Tabs\Tab::make('Shopify App')
                            ->schema([
                                Forms\Components\Placeholder::make('info')
                                    ->content('Configure your Shopify Partner app credentials. These are used for the merchant onboarding OAuth flow.')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('shopify_app_client_id')
                                    ->label('Client ID')
                                    ->placeholder('Enter your Shopify app Client ID')
                                    ->helperText('From your Shopify Partner Dashboard → Apps → Your App → Client credentials')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('shopify_app_client_secret')
                                    ->label('Client Secret')
                                    ->password()
                                    ->placeholder('Enter new secret to update (leave blank to keep current)')
                                    ->helperText('Only enter if you want to update the stored secret')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('shopify_app_redirect_uri')
                                    ->label('Redirect URI')
                                    ->url()
                                    ->placeholder('https://portal.oliv.tech/onboarding/shopify/callback')
                                    ->helperText('Must match the redirect URL configured in your Shopify Partner app')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('shopify_app_scopes')
                                    ->label('OAuth Scopes')
                                    ->placeholder('read_products,read_themes,read_content,read_orders,read_customers')
                                    ->helperText('Comma-separated list of Shopify API scopes')
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
                ->label('Save Settings')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }
}
