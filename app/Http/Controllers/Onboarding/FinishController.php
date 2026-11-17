<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\AppConfiguration;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class FinishController extends Controller
{
    /**
     * Step 5 of onboarding — completion screen.
     */
    public function show(Tenant $tenant): View|RedirectResponse
    {
        if (! filled($tenant->shopify_shop_domain) || ! filled($tenant->shopify_admin_token)) {
            return redirect()->to(ShopifyOnboardingController::signedShowUrl($tenant));
        }

        $config = AppConfiguration::where('tenant_id', $tenant->id)->first()?->config ?? [];

        return view('onboarding.finish', [
            'tenant' => $tenant,
            'appName' => $config['basic']['app_name'] ?? $tenant->name,
            'storefrontReady' => filled($tenant->shopify_storefront_token),
        ]);
    }

    public static function signedShowUrl(Tenant $tenant): string
    {
        return URL::signedRoute('onboarding.finish.show', ['tenant' => $tenant->id]);
    }
}
