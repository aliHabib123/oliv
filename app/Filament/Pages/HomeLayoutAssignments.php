<?php

namespace App\Filament\Pages;

use App\Models\HomeLayout;
use App\Models\Tenant;
use App\Services\Shopify\ShopifyStorefrontService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Storage;

class HomeLayoutAssignments extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Home Layout Assignments';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string | \UnitEnum | null $navigationGroup = 'App Settings';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.home-layout-assignments';

    public ?Tenant $tenant = null;
    public array $categories = [];
    public array $existingLayouts = [];
    public array $assignments = [];

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
        $this->loadCategories();
        $this->loadExistingLayouts();
        $this->loadAssignments();
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

    protected function loadCategories(): void
    {
        if (!$this->tenant) {
            return;
        }

        try {
            $shopifyService = new ShopifyStorefrontService();
            $this->categories = $shopifyService->getFlutterMenuLevel0Categories($this->tenant);
        } catch (\Throwable $e) {
            $this->categories = [];
            Notification::make()
                ->title('Failed to load categories')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function loadExistingLayouts(): void
    {
        if (!$this->tenant) {
            return;
        }

        $this->existingLayouts = HomeLayout::where('tenant_id', $this->tenant->id)
            ->whereNull('category_handle')
            ->orWhere('is_default', true)
            ->get()
            ->map(fn ($layout) => [
                'id' => $layout->id,
                'name' => $layout->name,
            ])
            ->toArray();
    }

    protected function loadAssignments(): void
    {
        if (!$this->tenant) {
            return;
        }

        // Load existing assignments
        $layouts = HomeLayout::where('tenant_id', $this->tenant->id)
            ->whereNotNull('category_handle')
            ->get()
            ->keyBy('category_handle');

        $this->assignments = [];
        foreach ($this->categories as $category) {
            $handle = $category['handle'];
            $layout = $layouts->get($handle);

            $this->assignments[$handle] = [
                'category_title' => $category['title'],
                'category_gid' => $category['gid'],
                'layout_id' => $layout?->id,
                'layout_name' => $layout?->name,
                'is_active' => $layout?->status === 'active',
                'has_assignment' => $layout !== null,
                'layout_data' => $layout?->layout_data ?? null,
            ];
        }
    }

    public function assignExistingLayout(string $categoryHandle, int $layoutId): void
    {
        if (!$this->tenant) {
            return;
        }

        try {
            $sourceLayout = HomeLayout::find($layoutId);
            if (!$sourceLayout || $sourceLayout->tenant_id !== $this->tenant->id) {
                throw new \Exception('Invalid layout selected');
            }

            $category = collect($this->categories)->firstWhere('handle', $categoryHandle);
            if (!$category) {
                throw new \Exception('Category not found');
            }

            // Check if assignment already exists
            $existingLayout = HomeLayout::where('tenant_id', $this->tenant->id)
                ->where('category_handle', $categoryHandle)
                ->first();

            if ($existingLayout) {
                // Update existing
                $existingLayout->update([
                    'layout_data' => $sourceLayout->layout_data,
                    'name' => $category['title'] . ' Layout',
                    'category_gid' => $category['gid'],
                    'category_title' => $category['title'],
                ]);
            } else {
                // Create new
                HomeLayout::create([
                    'tenant_id' => $this->tenant->id,
                    'category_handle' => $categoryHandle,
                    'category_gid' => $category['gid'],
                    'category_title' => $category['title'],
                    'is_default' => false,
                    'layout_data' => $sourceLayout->layout_data,
                    'name' => $category['title'] . ' Layout',
                    'status' => 'active',
                ]);
            }

            $this->loadAssignments();

            Notification::make()
                ->title('Layout Assigned')
                ->body("Successfully assigned layout to {$category['title']}")
                ->success()
                ->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Assignment Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function assignFromTemplate(string $categoryHandle): void
    {
        if (!$this->tenant) {
            return;
        }

        try {
            // Load template
            $templatePath = storage_path('app/templates/tenant_1_home_layout.json');
            if (!file_exists($templatePath)) {
                throw new \Exception('Template not found');
            }

            $templateLayout = json_decode(file_get_contents($templatePath), true);
            if (!$templateLayout) {
                throw new \Exception('Invalid template format');
            }

            $category = collect($this->categories)->firstWhere('handle', $categoryHandle);
            if (!$category) {
                throw new \Exception('Category not found');
            }

            // Check if assignment already exists
            $existingLayout = HomeLayout::where('tenant_id', $this->tenant->id)
                ->where('category_handle', $categoryHandle)
                ->first();

            if ($existingLayout) {
                // Update existing
                $existingLayout->update([
                    'layout_data' => $templateLayout,
                    'name' => $category['title'] . ' Layout',
                    'category_gid' => $category['gid'],
                    'category_title' => $category['title'],
                ]);
            } else {
                // Create new
                HomeLayout::create([
                    'tenant_id' => $this->tenant->id,
                    'category_handle' => $categoryHandle,
                    'category_gid' => $category['gid'],
                    'category_title' => $category['title'],
                    'is_default' => false,
                    'layout_data' => $templateLayout,
                    'name' => $category['title'] . ' Layout',
                    'status' => 'active',
                ]);
            }

            $this->loadAssignments();

            Notification::make()
                ->title('Template Imported')
                ->body("Successfully imported template for {$category['title']}")
                ->success()
                ->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Import Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function toggleStatus(string $categoryHandle): void
    {
        if (!$this->tenant) {
            return;
        }

        try {
            $layout = HomeLayout::where('tenant_id', $this->tenant->id)
                ->where('category_handle', $categoryHandle)
                ->firstOrFail();

            $newStatus = $layout->status === 'active' ? 'inactive' : 'active';
            $layout->update(['status' => $newStatus]);

            $this->loadAssignments();

            Notification::make()
                ->title('Status Updated')
                ->body("Layout is now {$newStatus}")
                ->success()
                ->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Update Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
