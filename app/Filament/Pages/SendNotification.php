<?php

namespace App\Filament\Pages;

use App\Models\DeviceToken;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OneSignal\OneSignalService;
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
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use UnitEnum;

class SendNotification extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-bell';

    protected static string | UnitEnum | null $navigationGroup = 'Messaging';

    protected static ?string $navigationLabel = 'Push';

    protected static ?string $title = 'Send Push Notification';

    protected static ?int $navigationSort = 2;

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

        $this->form->fill([]);
    }

    public static function canAccess(): bool
    {
        /** @var Authenticatable|null $user */
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole(['owner', 'admin']);
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

    public function send(): void
    {
        $data = $this->form->getState();

        if (!$this->tenant) {
            Notification::make()
                ->title('Error')
                ->body('Tenant not found')
                ->danger()
                ->send();
            return;
        }

        try {
            $oneSignal = new OneSignalService();

            $result = $oneSignal->sendToAllTenantDevices(
                tenant: $this->tenant,
                title: $data['title'],
                message: $data['message'],
                data: $data['custom_data'] ? json_decode($data['custom_data'], true) : null
            );

            if ($result['success']) {
                Notification::make()
                    ->title('Notification sent successfully')
                    ->body("Sent to {$result['recipients']} device(s)")
                    ->success()
                    ->send();

                $this->form->fill([
                    'title' => '',
                    'message' => '',
                    'custom_data' => '',
                ]);
            } else {
                Notification::make()
                    ->title('Failed to send notification')
                    ->body($result['error'] ?? 'Unknown error')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Notification Title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., New Sale Starting!')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('message')
                    ->label('Message')
                    ->required()
                    ->maxLength(500)
                    ->rows(4)
                    ->placeholder('e.g., Get 50% off on all items this weekend!')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('custom_data')
                    ->label('Custom Data (JSON)')
                    ->helperText('Optional JSON data to send with notification')
                    ->placeholder('{"screen": "products", "category_id": "123"}')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Placeholder::make('device_count')
                    ->label('Registered Devices')
                    ->content(function () {
                        if (!$this->tenant) {
                            return '0 devices';
                        }

                        $count = DeviceToken::where('tenant_id', $this->tenant->id)
                            ->where('provider', 'onesignal')
                            ->count();

                        return $count . ' device(s) will receive this notification';
                    })
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
            ->livewireSubmitHandler('send')
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
            $this->getSendAction(),
        ];
    }

    protected function getSendAction(): Action
    {
        return Action::make('send')
            ->label('Send Notification')
            ->icon('heroicon-o-paper-airplane')
            ->submit('send');
    }
}
