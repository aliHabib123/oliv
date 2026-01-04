<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
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

                            Forms\Components\TextInput::make('cta_target_id')
                                ->label('CTA target ID')
                                ->maxLength(255)
                                ->visible(fn (callable $get): bool => in_array($get('cta_target_type'), ['product', 'brand', 'category'], true)),

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
                                ->directory('home-layout')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('url')
                                ->label('URL')
                                ->maxLength(2048),
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
