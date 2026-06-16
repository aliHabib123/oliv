<?php

namespace App\Livewire;

use App\Models\OnboardingSession;
use App\Services\Onboarding\AppConfigGenerator;
use Livewire\Component;

class OnboardingWizard extends Component
{
    public int $currentStep = 1;
    public int $totalSteps = 7;

    public string $platform = '';
    public string $shopifyDomain = '';
    public array $storeData = [];
    public array $branding = [];
    public ?int $selectedThemeId = null;

    public ?OnboardingSession $session = null;

    public function mount(): void
    {
        $user = auth()->user();

        $this->session = OnboardingSession::whereHas('tenant', function ($q) use ($user) {
            $q->whereHas('users', fn ($uq) => $uq->where('users.id', $user->id));
        })->whereNull('completed_at')->latest()->first();

        if (! $this->session) {
            $tenant = $user->tenant;

            if (! $tenant) {
                $tenant = \App\Models\Tenant::create([
                    'name' => 'New Store',
                    'status' => 'onboarding',
                ]);
                $user->update(['tenant_id' => $tenant->id]);
            }

            $this->session = OnboardingSession::create([
                'tenant_id' => $tenant->id,
                'platform' => '',
                'step' => 1,
            ]);
        }

        $this->currentStep = $this->session->step;
        $this->hydrateFromSession();
    }

    public function nextStep(): void
    {
        $this->validateCurrentStep();
        $this->persistCurrentStep();
        $this->currentStep = min($this->currentStep + 1, $this->totalSteps);
        $this->session->update(['step' => $this->currentStep]);
    }

    public function previousStep(): void
    {
        $this->currentStep = max($this->currentStep - 1, 1);
        $this->session->update(['step' => $this->currentStep]);
    }

    public function selectPlatform(string $platform): void
    {
        $this->platform = $platform;
        $this->session->update(['platform' => $platform]);

        if ($platform === 'manual') {
            $this->currentStep = 3;
        } else {
            $this->currentStep = 2;
        }

        $this->session->update(['step' => $this->currentStep]);
    }

    public function selectTheme(int $themeId): void
    {
        $this->selectedThemeId = $themeId;
    }

    public function connectPublic(): void
    {
        $this->validate([
            'shopifyDomain' => 'required|string|min:3',
        ], [
            'shopifyDomain.required' => 'Please enter your Shopify store domain.',
        ]);

        $scraper = app(\App\Services\Onboarding\PublicShopifyScraper::class);

        try {
            $importedData = $scraper->scrape($this->shopifyDomain);
        } catch (\Exception $e) {
            $this->addError('shopifyDomain', $e->getMessage());
            return;
        }

        $domain = $importedData['store_info']['domain'] ?? $this->shopifyDomain;
        $storeName = $importedData['store_info']['name'] ?? $domain;

        // Create a new tenant for this store
        $tenant = \App\Models\Tenant::create([
            'name' => $storeName,
            'shopify_shop_domain' => $domain,
            'status' => 'active',
        ]);

        // Assign the user to the new tenant
        $user = auth()->user();
        $user->update(['tenant_id' => $tenant->id]);

        // Update session
        $this->session->update([
            'tenant_id' => $tenant->id,
            'shopify_domain' => $domain,
            'platform' => 'shopify',
            'imported_data' => $importedData,
        ]);

        // Hydrate from fresh data
        $this->session->refresh();
        $this->shopifyDomain = $domain;
        $this->storeData = array_merge(
            $importedData['store_info'] ?? [],
            ['product_count' => $importedData['catalog']['product_count'] ?? 0]
        );
        $this->hydrateFromSession();

        $this->currentStep = 3;
        $this->session->update(['step' => 3]);
    }

    public function generate(): void
    {
        $this->persistCurrentStep();

        $generator = app(AppConfigGenerator::class);
        $generator->generate($this->session->fresh());

        $this->currentStep = 7;
        $this->session->update(['step' => $this->currentStep]);
    }

    public function render()
    {
        return view('livewire.onboarding-wizard')
            ->layout('layouts.onboarding');
    }

    private function hydrateFromSession(): void
    {
        $this->platform = $this->session->platform ?? '';
        $this->shopifyDomain = $this->session->shopify_domain ?? '';

        $payload = $this->session->wizard_payload ?? [];
        $importedData = $this->session->imported_data ?? [];

        $this->storeData = $payload['store_data'] ?? $importedData['store_info'] ?? [];

        if (empty($this->storeData['product_count']) && ! empty($importedData['catalog']['product_count'])) {
            $this->storeData['product_count'] = $importedData['catalog']['product_count'];
        }

        $this->branding = $payload['branding'] ?? [];
        $this->selectedThemeId = $payload['theme_id'] ?? null;

        // Pre-fill branding from imported data if not already set
        if (empty($this->branding) && ! empty($importedData['branding'])) {
            $this->branding = [
                'app_name' => $importedData['store_info']['name'] ?? '',
                'app_icon_url' => $importedData['branding']['logo_url'] ?? null,
                'primary_color' => $importedData['branding']['primary_color'] ?? '#000000',
                'secondary_color' => $importedData['branding']['secondary_color'] ?? '#ffffff',
                'font_family' => $importedData['branding']['font_family'] ?? 'Inter',
                'splash_url' => '',
            ];
        }
    }

    private function validateCurrentStep(): void
    {
        match ($this->currentStep) {
            1 => $this->validate(['platform' => 'required|in:shopify,manual']),
            4 => $this->validate([
                'branding.app_name' => 'required|string|max:255',
                'branding.primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'branding.secondary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            ]),
            default => null,
        };
    }

    private function persistCurrentStep(): void
    {
        $payload = $this->session->wizard_payload ?? [];

        match ($this->currentStep) {
            1 => $this->session->update(['platform' => $this->platform]),
            3 => $this->session->update(['wizard_payload' => array_merge($payload, ['store_data' => $this->storeData])]),
            4 => $this->session->update(['wizard_payload' => array_merge($payload, ['branding' => $this->branding])]),
            5 => $this->session->update(['wizard_payload' => array_merge($payload, ['theme_id' => $this->selectedThemeId])]),
            6 => $this->session->update(['wizard_payload' => array_merge($payload, [
                'branding' => $this->branding,
                'theme_id' => $this->selectedThemeId,
            ])]),
            default => null,
        };
    }
}
