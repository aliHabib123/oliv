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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire as LivewireComponent;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use UnitEnum;

class Introduction extends Page implements HasForms
{
    use CanUseDatabaseTransactions;
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';

    protected static string | UnitEnum | null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Introduction';

    protected static ?string $title = 'Introduction Screen';

    public ?array $data = [];

    public ?Tenant $tenant = null;

    // Tracks live uploaded banner (temp URL or saved path)
    public ?string $liveBannerImage = null;

    // Tracks live feature sections with icon URLs (temp or saved)
    public array $liveFeatureSections = [];

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

        // Initialize liveBannerImage with existing saved image
        if (isset($this->data['banner_image']) && is_string($this->data['banner_image'])) {
            $this->liveBannerImage = $this->data['banner_image'];
        }

        // Initialize liveFeatureSections with existing saved sections
        // Use array_values to ensure sequential arrays for proper JSON encoding
        if (isset($this->data['feature_sections']) && is_array($this->data['feature_sections'])) {
            $sections = array_values($this->data['feature_sections']);
            foreach ($sections as &$section) {
                if (isset($section['items']) && is_array($section['items'])) {
                    $section['items'] = array_values($section['items']);
                }
            }
            $this->liveFeatureSections = $sections;
        }

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
                ->live(onBlur: false)
                ->afterStateUpdated(function ($state) {
                    // Update live banner state
                    if ($state === null) {
                        $this->liveBannerImage = null;
                    } elseif ($state instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                        try {
                            $this->liveBannerImage = $state->temporaryUrl();
                        } catch (\Exception $e) {
                            $this->liveBannerImage = null;
                        }
                    } else {
                        $this->liveBannerImage = is_string($state) ? $state : null;
                    }

                    // Dispatch with live banner
                    $data = $this->data;
                    $data['banner_image'] = $this->liveBannerImage;
                    $this->dispatch('preview-updated', data: $data);
                })
                ->columnSpanFull(),

            Forms\Components\TextInput::make('welcome_title')
                ->label('Welcome Title')
                ->maxLength(255)
                ->required()
                ->default('WELCOME')
                ->live(onBlur: false)
                ->afterStateUpdated(function ($state, $get) {
                    $this->dispatch('preview-updated', data: [
                        'banner_image' => $this->liveBannerImage,
                        'welcome_title' => $state,
                        'welcome_text' => $get('welcome_text'),
                        'feature_sections' => $this->liveFeatureSections,
                    ]);
                }),

            Forms\Components\Textarea::make('welcome_text')
                ->label('Welcome Text')
                ->maxLength(1000)
                ->rows(4)
                ->required()
                ->live(onBlur: false)
                ->afterStateUpdated(function ($state, $get) {
                    $this->dispatch('preview-updated', data: [
                        'banner_image' => $this->liveBannerImage,
                        'welcome_title' => $get('welcome_title'),
                        'welcome_text' => $state,
                        'feature_sections' => $this->liveFeatureSections,
                    ]);
                })
                ->columnSpanFull(),

            Forms\Components\Repeater::make('feature_sections')
                ->label('Feature Section')
                ->live(onBlur: false)
                ->afterStateUpdated(function ($state, $get) {
                    // Update live feature sections with icon conversions
                    $sections = is_array($state) ? array_values($state) : [];

                    foreach ($sections as $sKey => &$section) {
                        if (isset($section['items']) && is_array($section['items'])) {
                            $section['items'] = array_values($section['items']);
                            foreach ($section['items'] as &$item) {
                                if (isset($item['icon'])) {
                                    $icon = $item['icon'];

                                    // Filament wraps uploads in UUID-keyed arrays
                                    // Unwrap if needed: ['uuid' => TemporaryUploadedFile] or ['uuid' => 'path']
                                    if (is_array($icon) && !empty($icon)) {
                                        $icon = reset($icon); // Get first value
                                    }

                                    if ($icon instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                        try {
                                            $item['icon'] = $icon->temporaryUrl();
                                        } catch (\Exception $e) {
                                            $item['icon'] = null;
                                        }
                                    } elseif (is_string($icon)) {
                                        // Keep string paths as-is
                                        $item['icon'] = $icon;
                                    } else {
                                        // Invalid/null
                                        $item['icon'] = null;
                                    }
                                }
                            }
                        }
                    }
                    $this->liveFeatureSections = $sections;

                    $this->dispatch('preview-updated', data: [
                        'banner_image' => $this->liveBannerImage,
                        'welcome_title' => $get('welcome_title'),
                        'welcome_text' => $get('welcome_text'),
                        'feature_sections' => $this->liveFeatureSections,
                    ]);
                })
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
                // Two-column layout: builder on left, preview on right
                Grid::make(2)
                    ->schema([
                        // Left column: Form builder
                        $this->getFormContentComponent(),

                        // Right column: Live preview with sticky positioning to stay on screen during scroll
                        LivewireComponent::make(\App\Livewire\IntroductionPreview::class)
                            ->key('introduction-preview')
                            ->data([
                                'introData' => $this->data,
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
}
