<?php

namespace App\Filament\Pages;

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
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use UnitEnum;

class Introduction extends Page implements HasForms
{
    use CanUseDatabaseTransactions;
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';

    protected static string | UnitEnum | null $navigationGroup = 'App Settings';

    protected static ?string $navigationLabel = 'Introduction';

    protected static ?string $title = 'Introduction Screen';

    public ?array $data = [];

    public ?Tenant $tenant = null;

    public $banner_image = null;
    public $welcome_title = null;
    public $welcome_text = null;
    public $feature_sections = [];

    public function mount(): void
    {
        /** @var Authenticatable|null $user */
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'hasAnyRole')) {
            abort(403);
        }

        // Super admin: use the existing Tenants resource to choose which tenant's intro to edit.
        if ($user->hasRole('super_admin')) {
            $this->redirect(route('filament.admin.resources.tenants.index'));

            return;
        }

        $this->tenant = $this->resolveTenant();

        $introConfig = $this->tenant->intro_config;
        $this->data = is_array($introConfig) ? $introConfig : [];

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

            $this->tenant->intro_config = $data;
            $this->tenant->save();

            $this->commitDatabaseTransaction();

            Notification::make()
                ->title('Introduction configuration saved')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->rollBackDatabaseTransaction();

            Notification::make()
                ->title('Failed to save introduction configuration')
                ->danger()
                ->send();

            throw $e;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\FileUpload::make('banner_image')
                ->label('Banner Image')
                ->image()
                ->disk('public')
                ->directory('introduction')
                ->required()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('welcome_title')
                ->label('Welcome Title')
                ->maxLength(255)
                ->required()
                ->default('WELCOME'),

            Forms\Components\Textarea::make('welcome_text')
                ->label('Welcome Text')
                ->maxLength(1000)
                ->rows(4)
                ->required()
                ->columnSpanFull(),

            Forms\Components\Repeater::make('feature_sections')
                ->label('Feature Section')
                ->schema([
                    Forms\Components\TextInput::make('section_title')
                        ->label('Section Title')
                        ->maxLength(255)
                        ->required()
                        ->placeholder('e.g., WHY CHOOSE US?')
                        ->columnSpanFull(),

                    Forms\Components\Repeater::make('items')
                        ->label('Features (max 4)')
                        ->schema([
                            Forms\Components\FileUpload::make('icon')
                                ->label('Icon')
                                ->image()
                                ->disk('public')
                                ->directory('introduction/icons')
                                ->required(),

                            Forms\Components\TextInput::make('title')
                                ->label('Title')
                                ->maxLength(255)
                                ->required()
                                ->placeholder('e.g., FREE DELIVERY'),
                        ])
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->addActionLabel('Add feature')
                        ->defaultItems(0)
                        ->maxItems(4)
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['section_title'] ?? null)
                ->addActionLabel('Add section')
                ->defaultItems(0)
                ->maxItems(1)
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
