<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\AppConfiguration;
use App\Models\Tenant;
use App\Services\Shopify\ShopifyStorefrontService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandingController extends Controller
{
    public function __construct(
        private ShopifyStorefrontService $storefront
    ) {}

    /**
     * Step 2 of onboarding — confirm app name, logo and colors.
     * Pre-fills from the store's Shopify brand assets where not already set.
     */
    public function show(Tenant $tenant): View|RedirectResponse
    {
        if (! $this->isShopifyConnected($tenant)) {
            return redirect()->to(ShopifyOnboardingController::signedShowUrl($tenant));
        }

        $basic = $this->basicConfig($tenant);

        // Only reach out to Shopify if we still need a default to suggest.
        $suggested = ['name' => null, 'logo_url' => null, 'primary_color' => null, 'secondary_color' => null];
        $needsSuggestion = blank($basic['app_name'] ?? null)
            || blank($basic['logo'] ?? null)
            || blank($basic['primary_color'] ?? null)
            || blank($basic['secondary_color'] ?? null);

        if ($needsSuggestion) {
            $suggested = $this->storefront->getBrandAssets($tenant);
        }

        return view('onboarding.branding', [
            'tenant' => $tenant,
            'appName' => $basic['app_name'] ?? $suggested['name'],
            'primaryColor' => $basic['primary_color'] ?? $suggested['primary_color'] ?? '#252621',
            'secondaryColor' => $basic['secondary_color'] ?? $suggested['secondary_color'] ?? '#B98B69',
            'logoPath' => $basic['logo'] ?? null,
            'suggestedLogoUrl' => blank($basic['logo'] ?? null) ? $suggested['logo_url'] : null,
        ]);
    }

    /**
     * Persist branding into the tenant's AppConfiguration.
     */
    public function save(Request $request, Tenant $tenant): RedirectResponse
    {
        if (! $this->isShopifyConnected($tenant)) {
            return redirect()->to(ShopifyOnboardingController::signedShowUrl($tenant));
        }

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'suggested_logo_url' => ['nullable', 'url'],
        ]);

        $config = AppConfiguration::firstOrCreate(['tenant_id' => $tenant->id], ['config' => []]);
        $full = $config->config ?? [];
        $basic = $full['basic'] ?? [];

        $basic['app_name'] = $validated['app_name'];
        $basic['primary_color'] = $validated['primary_color'] ?? ($basic['primary_color'] ?? null);
        $basic['secondary_color'] = $validated['secondary_color'] ?? ($basic['secondary_color'] ?? null);

        $logoPath = $this->resolveLogo($request, $validated, $basic['logo'] ?? null);
        if ($logoPath) {
            $basic['logo'] = $logoPath;
        }

        $full['basic'] = $basic;
        $config->config = $full;
        $config->save();

        return redirect()->to(CategoriesController::signedShowUrl($tenant))
            ->with('status', 'Your branding has been saved.');
    }

    /**
     * Decide which logo to store: an uploaded file wins, otherwise pull the
     * suggested Shopify brand logo into local storage, otherwise keep existing.
     */
    private function resolveLogo(Request $request, array $validated, ?string $existing): ?string
    {
        if ($request->hasFile('logo')) {
            return $request->file('logo')->store('app-settings', 'public');
        }

        if (blank($existing) && ! empty($validated['suggested_logo_url'])) {
            return $this->downloadRemoteLogo($validated['suggested_logo_url']);
        }

        return $existing;
    }

    private function downloadRemoteLogo(string $url): ?string
    {
        try {
            $response = Http::timeout(15)->get($url);

            if (! $response->ok()) {
                return null;
            }

            $extension = match (strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION))) {
                'png' => 'png',
                'jpg', 'jpeg' => 'jpg',
                'webp' => 'webp',
                'svg' => 'svg',
                default => 'png',
            };

            $path = 'app-settings/'.Str::uuid().'.'.$extension;
            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function isShopifyConnected(Tenant $tenant): bool
    {
        return filled($tenant->shopify_shop_domain) && filled($tenant->shopify_admin_token);
    }

    private function basicConfig(Tenant $tenant): array
    {
        $config = AppConfiguration::where('tenant_id', $tenant->id)->first();

        return $config?->config['basic'] ?? [];
    }

    public static function signedShowUrl(Tenant $tenant): string
    {
        return URL::signedRoute('onboarding.branding.show', ['tenant' => $tenant->id]);
    }
}
