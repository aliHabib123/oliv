<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\AppConfiguration;
use App\Models\Tenant;
use App\Services\Shopify\ShopifyAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class CategoriesController extends Controller
{
    public function __construct(
        private ShopifyAdminService $admin
    ) {}

    /**
     * Step 3 of onboarding — pick which Shopify collections become the app's
     * main categories. On save we build the `flutter-menu` from the selection.
     */
    public function show(Tenant $tenant): View|RedirectResponse
    {
        if (! $this->isShopifyConnected($tenant)) {
            return redirect()->to(ShopifyOnboardingController::signedShowUrl($tenant));
        }

        $collections = [];
        $error = null;

        try {
            $collections = $this->admin->getCollections($tenant);
        } catch (\Throwable $e) {
            Log::error('Onboarding categories: failed to load collections', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            $error = 'We could not load your collections from Shopify. Please try again.';
        }

        return view('onboarding.categories', [
            'tenant' => $tenant,
            'collections' => $collections,
            'selectedIds' => $this->savedSelection($tenant),
            'loadError' => $error,
        ]);
    }

    /**
     * Build/refresh the flutter-menu from the selected collections.
     */
    public function save(Request $request, Tenant $tenant): RedirectResponse
    {
        if (! $this->isShopifyConnected($tenant)) {
            return redirect()->to(ShopifyOnboardingController::signedShowUrl($tenant));
        }

        $validated = $request->validate([
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['string'],
        ], [
            'selected_ids.required' => 'Pick at least one collection to use as a main category.',
        ]);

        try {
            // Re-fetch from Shopify so titles/GIDs are trusted, ordered by the selection.
            $all = collect($this->admin->getCollections($tenant))->keyBy('id');
            $chosen = collect($validated['selected_ids'])
                ->map(fn (string $id): ?array => $all->get($id))
                ->filter()
                ->values()
                ->all();

            if (empty($chosen)) {
                return back()->withErrors(['selected_ids' => 'Those collections could not be found in your store.']);
            }

            $this->admin->upsertFlutterMenu($tenant, $chosen);
        } catch (\Throwable $e) {
            Log::error('Onboarding categories: failed to build flutter-menu', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['selected_ids' => 'We could not save your categories to Shopify. Please try again.']);
        }

        $this->persistSelection($tenant, $validated['selected_ids']);

        return redirect()->to(StoreInfoController::signedShowUrl($tenant))
            ->with('status', 'Your categories have been set up.');
    }

    private function persistSelection(Tenant $tenant, array $collectionIds): void
    {
        $config = AppConfiguration::firstOrCreate(['tenant_id' => $tenant->id], ['config' => []]);
        $full = $config->config ?? [];
        $full['categories'] = ['collection_ids' => array_values($collectionIds)];
        // Surfacing main-category tabs depends on this sync flag elsewhere in the app.
        $full['basic'] = array_merge($full['basic'] ?? [], ['sync_level_0_categories' => true]);
        $config->config = $full;
        $config->save();
    }

    /**
     * @return array<int, string>
     */
    private function savedSelection(Tenant $tenant): array
    {
        $config = AppConfiguration::where('tenant_id', $tenant->id)->first();

        return $config?->config['categories']['collection_ids'] ?? [];
    }

    private function isShopifyConnected(Tenant $tenant): bool
    {
        return filled($tenant->shopify_shop_domain) && filled($tenant->shopify_admin_token);
    }

    public static function signedShowUrl(Tenant $tenant): string
    {
        return URL::signedRoute('onboarding.categories.show', ['tenant' => $tenant->id]);
    }
}
