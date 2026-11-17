<?php

namespace App\Filament\Pages;

use App\Services\TenantResetService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Auth\Authenticatable;
use UnitEnum;

class ResetTenants extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-trash';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Reset Tenants';

    protected static ?string $title = 'Reset Tenants';

    protected static ?int $navigationSort = 98;

    protected string $view = 'filament.pages.reset-tenants';

    /** @var array<string, int> */
    public array $counts = [];

    public function mount(): void
    {
        /** @var Authenticatable|null $user */
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole('super_admin')) {
            abort(403);
        }

        $this->counts = app(TenantResetService::class)->counts();
    }

    public static function canAccess(): bool
    {
        /** @var Authenticatable|null $user */
        $user = auth()->user();

        return $user && method_exists($user, 'hasRole') && $user->hasRole('super_admin');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset')
                ->label('Delete all tenants')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete all tenants?')
                ->modalDescription('This permanently deletes every tenant and all related data — users, subscriptions, payment gateways, app settings, carts, orders and checkout history. Super-admin accounts and global catalogue data are kept. This cannot be undone.')
                ->modalSubmitActionLabel('Yes, delete everything')
                ->schema([
                    TextInput::make('confirmation')
                        ->label('Type RESET to confirm')
                        ->placeholder('RESET')
                        ->required()
                        ->rule('in:RESET')
                        ->validationMessages(['in' => 'You must type RESET exactly to confirm.']),
                ])
                ->action(function (): void {
                    /** @var Authenticatable|null $user */
                    $user = auth()->user();

                    if (! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole('super_admin')) {
                        abort(403);
                    }

                    $summary = app(TenantResetService::class)->reset();

                    $this->counts = app(TenantResetService::class)->counts();

                    Notification::make()
                        ->title('Tenants reset')
                        ->body('Removed '.($summary['Tenants'] ?? 0).' tenant(s) and all related data.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
