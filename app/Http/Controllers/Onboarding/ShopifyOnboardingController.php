<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Shopify\ShopifyOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShopifyOnboardingController extends Controller
{
    public function __construct(
        private ShopifyOAuthService $oauth
    ) {}

    /**
     * Step 1 of onboarding — connect the tenant's Shopify store.
     * Reached via a signed URL after successful subscription payment.
     */
    public function show(Tenant $tenant): View
    {
        return view('onboarding.shopify', [
            'tenant' => $tenant,
            'connected' => filled($tenant->shopify_shop_domain) && filled($tenant->shopify_admin_token),
        ]);
    }

    /**
     * Validate the entered shop domain and redirect to Shopify's consent screen.
     */
    public function connect(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'shop_domain' => ['required', 'string', 'max:255'],
        ]);

        $shop = $this->oauth->normalizeShopDomain($validated['shop_domain']);

        if (! $shop) {
            return back()
                ->withInput()
                ->withErrors(['shop_domain' => 'Enter a valid Shopify store URL, e.g. your-store.myshopify.com']);
        }

        if (blank(config('services.shopify.client_id')) || blank(config('services.shopify.client_secret'))) {
            Log::error('Shopify app credentials are not configured.');

            return back()->withErrors(['shop_domain' => 'Shopify connection is not available right now. Please contact support.']);
        }

        $state = Str::random(40);

        // Bind this OAuth attempt to the tenant + shop for verification on callback.
        $request->session()->put('shopify_oauth', [
            'state' => $state,
            'tenant_id' => $tenant->id,
            'shop' => $shop,
        ]);

        $url = $this->oauth->buildAuthorizeUrl($shop, $state, route('onboarding.shopify.callback'));

        return redirect()->away($url);
    }

    /**
     * OAuth callback: verify, exchange the code for tokens, and persist them.
     */
    public function callback(Request $request): RedirectResponse
    {
        $session = $request->session()->pull('shopify_oauth');
        $shop = $request->query('shop');
        $code = $request->query('code');
        $state = $request->query('state');

        // CSRF / session binding check.
        if (! is_array($session)
            || ! is_string($state)
            || ! hash_equals($session['state'] ?? '', $state)
            || ($session['shop'] ?? null) !== $shop) {
            return $this->failRedirect($session['tenant_id'] ?? null, 'The connection request expired or was invalid. Please try again.');
        }

        $tenant = Tenant::find($session['tenant_id'] ?? null);

        if (! $tenant || ! is_string($shop) || ! is_string($code)) {
            return $this->failRedirect($session['tenant_id'] ?? null, 'We could not complete the connection. Please try again.');
        }

        // Verify the request genuinely came from Shopify.
        if (! $this->oauth->verifyHmac($request->query())) {
            Log::warning('Shopify OAuth callback HMAC verification failed', ['shop' => $shop, 'tenant_id' => $tenant->id]);

            return $this->failRedirect($tenant->id, 'Could not verify the response from Shopify. Please try again.');
        }

        $tokens = $this->oauth->exchangeCodeForToken($shop, $code);

        if (! $tokens) {
            return $this->failRedirect($tenant->id, 'Shopify rejected the connection. Please try again.');
        }

        $storefrontToken = $this->oauth->createStorefrontToken($shop, $tokens['access_token']);

        $this->oauth->storeCredentials($tenant, $shop, $tokens, $storefrontToken);

        Log::info('Shopify store connected for tenant', [
            'tenant_id' => $tenant->id,
            'shop' => $shop,
            'storefront_token' => $storefrontToken ? 'created' : 'missing',
        ]);

        // Storefront token is required for the app's catalog; if it failed, keep the
        // merchant on the Shopify step with a warning instead of advancing.
        if (! $storefrontToken) {
            return redirect()
                ->to(self::signedShowUrl($tenant))
                ->with('warning', 'Your store is connected, but we could not set up catalog access automatically. Our team will follow up.');
        }

        return redirect()->to(BrandingController::signedShowUrl($tenant));
    }

    private function failRedirect(?int $tenantId, string $message): RedirectResponse
    {
        $tenant = $tenantId ? Tenant::find($tenantId) : null;

        if (! $tenant) {
            return redirect()->route('subscriptions.index')->withErrors(['shop_domain' => $message]);
        }

        return redirect()->to(self::signedShowUrl($tenant))->withErrors(['shop_domain' => $message]);
    }

    /**
     * Generate a signed URL for the onboarding step (prevents tenant enumeration).
     */
    public static function signedShowUrl(Tenant $tenant): string
    {
        return URL::signedRoute('onboarding.shopify.show', ['tenant' => $tenant->id]);
    }
}
