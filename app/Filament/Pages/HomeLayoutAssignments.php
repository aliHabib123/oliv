<?php

namespace App\Filament\Pages;

use App\Models\HomeLayout;
use App\Models\Tenant;
use App\Models\Template;
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

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.home-layout-assignments';

    public ?Tenant $tenant = null;
    public array $categories = [];
    public array $existingLayouts = [];
    public array $assignments = [];
    public array $availableTemplates = [];
    public array $otherCategoryLayouts = [];
    public bool $flutterMenuExists = false;
    public bool $flutterMenuHasItems = false;

    public bool $previewOpen = false;
    public array $previewLayoutData = [];
    public string $previewTitle = '';

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
        $this->checkFlutterMenu();

        if ($this->flutterMenuExists && $this->flutterMenuHasItems) {
            $this->loadCategories();
            $this->loadExistingLayouts();
            $this->loadAssignments();
            $this->loadAvailableTemplates();
            $this->loadOtherCategoryLayouts();
        }
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

    protected function checkFlutterMenu(): void
    {
        if (!$this->tenant) {
            return;
        }

        $shopifyService = new ShopifyStorefrontService();

        try {
            $menu = $shopifyService->getMenuByHandle($this->tenant, 'flutter-menu');

            if (!$menu) {
                $this->flutterMenuExists = false;
                $this->flutterMenuHasItems = false;
                return;
            }

            $this->flutterMenuExists = true;
            $this->flutterMenuHasItems = !empty($menu['items']);

        } catch (\Throwable $e) {
            $this->flutterMenuExists = false;
            $this->flutterMenuHasItems = false;
        }
    }

    protected function loadCategories(): void
    {
        if (!$this->tenant) {
            return;
        }

        $shopifyService = new ShopifyStorefrontService();

        try {
            $menu = $shopifyService->getMenuByHandle($this->tenant, 'flutter-menu');

            if (!$menu || empty($menu['items'])) {
                $this->categories = [];
                return;
            }

            foreach ($menu['items'] as $item) {
                if (isset($item['resourceId']) && isset($item['title'])) {
                    $this->categories[] = [
                        'handle' => basename(parse_url($item['url'], PHP_URL_PATH)),
                        'title' => $item['title'],
                        'gid' => $item['resourceId'],
                    ];
                }
            }
        } catch (\Throwable $e) {
            $this->categories = [];
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

    protected function loadAvailableTemplates(): void
    {
        $this->availableTemplates = [];

        // Add tenant's existing layout as a template option if it exists
        if ($this->tenant && !empty($this->tenant->home_layout)) {
            $this->availableTemplates[] = [
                'name' => 'Tenant Default Layout',
                'type' => 'tenant',
            ];
        }

        // Load DB templates (created in Templates section)
        $dbTemplates = Template::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        foreach ($dbTemplates as $template) {
            if (empty($template->layout_json)) {
                continue;
            }

            $this->availableTemplates[] = [
                'name' => $template->name,
                'type' => 'db',
                'template_id' => $template->id,
            ];
        }

        // Load file-based templates
        $templatesPath = storage_path('app/templates');
        if (!is_dir($templatesPath)) {
            return;
        }

        $files = glob($templatesPath . '/*.json');
        foreach ($files as $file) {
            $filename = basename($file);

            $this->availableTemplates[] = [
                'name' => str_replace(['_', '.json'], [' ', ''], ucfirst($filename)),
                'type' => 'file',
                'filename' => $filename,
            ];
        }
    }

    protected function loadOtherCategoryLayouts(): void
    {
        if (!$this->tenant) {
            return;
        }

        $this->otherCategoryLayouts = HomeLayout::where('tenant_id', $this->tenant->id)
            ->whereNotNull('category_handle')
            ->whereNotNull('layout_data')
            ->get()
            ->map(fn ($layout) => [
                'id' => $layout->id,
                'category_handle' => $layout->category_handle,
                'category_title' => $layout->category_title,
                'name' => $layout->name,
            ])
            ->toArray();
    }

    public function openTemplatePreview(string $templateType, string $templateIdentifier): void
    {
        $this->previewTitle = '';
        $this->previewLayoutData = [];

        try {
            $layout = $this->loadTemplateLayoutForPreview($templateType, $templateIdentifier);

            if (! is_array($layout)) {
                throw new \Exception('Invalid template format');
            }

            // Guard: avoid rendering huge legacy project JSON (eg GrapesJS assets/base64)
            $encoded = json_encode($layout);
            if ($encoded !== false && strlen($encoded) > 250_000) {
                throw new \Exception('Template preview is too large to render.');
            }

            $this->previewLayoutData = $layout;
            $this->previewTitle = $this->resolveTemplatePreviewTitle($templateType, $templateIdentifier);
            $this->previewOpen = true;
        } catch (\Throwable $e) {
            $this->previewOpen = false;
            $this->previewLayoutData = [];

            Notification::make()
                ->title('Preview Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function openCategoryPreview(string $sourceHandle): void
    {
        $this->previewTitle = '';
        $this->previewLayoutData = [];

        try {
            if (! $this->tenant) {
                throw new \Exception('Tenant not found');
            }

            $layout = HomeLayout::query()
                ->where('tenant_id', $this->tenant->id)
                ->where('category_handle', $sourceHandle)
                ->value('layout_data');

            if (! is_array($layout)) {
                throw new \Exception('No preview available');
            }

            $encoded = json_encode($layout);
            if ($encoded !== false && strlen($encoded) > 250_000) {
                throw new \Exception('Preview is too large to render.');
            }

            $this->previewLayoutData = $layout;
            $this->previewTitle = (string) (collect($this->categories)->firstWhere('handle', $sourceHandle)['title'] ?? 'Category Preview');
            $this->previewOpen = true;
        } catch (\Throwable $e) {
            $this->previewOpen = false;
            $this->previewLayoutData = [];

            Notification::make()
                ->title('Preview Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function closePreview(): void
    {
        $this->previewOpen = false;
    }

    protected function loadTemplateLayoutForPreview(string $templateType, string $templateIdentifier): array
    {
        if (! $this->tenant) {
            throw new \Exception('Tenant not found');
        }

        if ($templateType === 'tenant') {
            if (empty($this->tenant->home_layout) || ! is_array($this->tenant->home_layout)) {
                throw new \Exception('Tenant default layout not found');
            }

            return $this->tenant->home_layout;
        }

        if ($templateType === 'db') {
            $template = Template::query()
                ->where('is_active', true)
                ->find($templateIdentifier);

            if (! $template || empty($template->layout_json)) {
                throw new \Exception('Template not found');
            }

            $decoded = json_decode($template->layout_json, true);
            if (! is_array($decoded)) {
                throw new \Exception('Invalid template format');
            }

            return $decoded;
        }

        $templatePath = storage_path('app/templates/' . $templateIdentifier);
        if (! file_exists($templatePath)) {
            throw new \Exception('Template not found');
        }

        $decoded = json_decode(file_get_contents($templatePath), true);
        if (! is_array($decoded)) {
            throw new \Exception('Invalid template format');
        }

        return $decoded;
    }

    protected function resolveTemplatePreviewTitle(string $templateType, string $templateIdentifier): string
    {
        if ($templateType === 'tenant') {
            return 'Tenant Default Layout';
        }

        if ($templateType === 'db') {
            $template = Template::query()->find($templateIdentifier);
            return (string) ($template?->name ?? 'Template');
        }

        return (string) $templateIdentifier;
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

    public function assignFromTemplate(string $categoryHandle, string $templateIdentifier, string $templateType = 'file'): void
    {
        if (!$this->tenant) {
            return;
        }

        try {
            // Load template based on type
            if ($templateType === 'tenant') {
                // Use tenant's home_layout
                if (empty($this->tenant->home_layout)) {
                    throw new \Exception('Tenant default layout not found');
                }
                $templateLayout = $this->tenant->home_layout;
            } elseif ($templateType === 'db') {
                $template = Template::query()
                    ->where('is_active', true)
                    ->find($templateIdentifier);

                if (! $template || empty($template->layout_json)) {
                    throw new \Exception('Template not found');
                }

                $templateLayout = json_decode($template->layout_json, true);

                if (! is_array($templateLayout)) {
                    throw new \Exception('Invalid template format');
                }
            } else {
                // Load from file
                $templatePath = storage_path('app/templates/' . $templateIdentifier);
                if (!file_exists($templatePath)) {
                    throw new \Exception('Template not found');
                }

                $templateLayout = json_decode(file_get_contents($templatePath), true);
                if (!$templateLayout) {
                    throw new \Exception('Invalid template format');
                }
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
            $this->loadOtherCategoryLayouts();

            Notification::make()
                ->title('Template Assigned')
                ->body("Successfully assigned template to {$category['title']}")
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

    public function assignFromCategory(string $targetHandle, string $sourceHandle): void
    {
        if (!$this->tenant) {
            return;
        }

        try {
            // Find source layout
            $sourceLayout = HomeLayout::where('tenant_id', $this->tenant->id)
                ->where('category_handle', $sourceHandle)
                ->firstOrFail();

            $targetCategory = collect($this->categories)->firstWhere('handle', $targetHandle);
            if (!$targetCategory) {
                throw new \Exception('Target category not found');
            }

            // Check if target already has a layout
            $existingLayout = HomeLayout::where('tenant_id', $this->tenant->id)
                ->where('category_handle', $targetHandle)
                ->first();

            if ($existingLayout) {
                // Update existing
                $existingLayout->update([
                    'layout_data' => $sourceLayout->layout_data,
                    'name' => $targetCategory['title'] . ' Layout',
                    'category_gid' => $targetCategory['gid'],
                    'category_title' => $targetCategory['title'],
                ]);
            } else {
                // Create new
                HomeLayout::create([
                    'tenant_id' => $this->tenant->id,
                    'category_handle' => $targetHandle,
                    'category_gid' => $targetCategory['gid'],
                    'category_title' => $targetCategory['title'],
                    'is_default' => false,
                    'layout_data' => $sourceLayout->layout_data,
                    'name' => $targetCategory['title'] . ' Layout',
                    'status' => 'active',
                ]);
            }

            $this->loadAssignments();
            $this->loadOtherCategoryLayouts();

            Notification::make()
                ->title('Layout Assigned')
                ->body("Successfully assigned layout from {$sourceLayout->category_title} to {$targetCategory['title']}")
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
