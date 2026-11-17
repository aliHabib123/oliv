<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\AppConfiguration;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class StoreInfoController extends Controller
{
    /**
     * Step 4 of onboarding — contact details, social links and policy URLs.
     * All fields are optional; the merchant can finish without them.
     */
    public function show(Tenant $tenant): View|RedirectResponse
    {
        if (! $this->isShopifyConnected($tenant)) {
            return redirect()->to(ShopifyOnboardingController::signedShowUrl($tenant));
        }

        $config = AppConfiguration::where('tenant_id', $tenant->id)->first()?->config ?? [];

        return view('onboarding.store-info', [
            'tenant' => $tenant,
            'contact' => $config['contact'] ?? [],
            'social' => $config['social_media'] ?? [],
            'pages' => $config['static_pages'] ?? [],
        ]);
    }

    /**
     * Persist store info, mark onboarding complete, and advance to Finish.
     */
    public function save(Request $request, Tenant $tenant): RedirectResponse
    {
        if (! $this->isShopifyConnected($tenant)) {
            return redirect()->to(ShopifyOnboardingController::signedShowUrl($tenant));
        }

        $validated = $request->validate([
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.phone' => ['nullable', 'string', 'max:255'],
            'contact.address' => ['nullable', 'string', 'max:500'],
            'social_media.facebook' => ['nullable', 'url', 'max:255'],
            'social_media.instagram' => ['nullable', 'url', 'max:255'],
            'social_media.twitter' => ['nullable', 'url', 'max:255'],
            'social_media.tiktok' => ['nullable', 'url', 'max:255'],
            'static_pages.privacy_policy' => ['nullable', 'url', 'max:255'],
            'static_pages.return_policy' => ['nullable', 'url', 'max:255'],
            'static_pages.shipping_policy' => ['nullable', 'url', 'max:255'],
            'static_pages.about_us' => ['nullable', 'url', 'max:255'],
        ]);

        $config = AppConfiguration::firstOrCreate(['tenant_id' => $tenant->id], ['config' => []]);
        $full = $config->config ?? [];

        $full['contact'] = array_filter($validated['contact'] ?? [], fn ($v) => filled($v));
        $full['social_media'] = array_filter($validated['social_media'] ?? [], fn ($v) => filled($v));
        $full['static_pages'] = array_filter($validated['static_pages'] ?? [], fn ($v) => filled($v));
        $full['onboarding'] = ['completed_at' => now()->toIso8601String()];

        $config->config = $full;
        $config->save();

        return redirect()->to(FinishController::signedShowUrl($tenant));
    }

    private function isShopifyConnected(Tenant $tenant): bool
    {
        return filled($tenant->shopify_shop_domain) && filled($tenant->shopify_admin_token);
    }

    public static function signedShowUrl(Tenant $tenant): string
    {
        return URL::signedRoute('onboarding.store-info.show', ['tenant' => $tenant->id]);
    }
}
