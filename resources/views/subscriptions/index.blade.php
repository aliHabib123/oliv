<!DOCTYPE html>
<html lang="en" style="height: 100%;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Package - Oliv</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex flex-col" style="min-height: 100vh; margin: 0;">
    <header class="bg-white shadow-sm py-4" style="position: fixed; top: 0; left: 0; right: 0; z-index: 20; width: 100vw;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <img src="{{ asset('img/IMG_5398.png') }}" alt="Oliv Logo" class="h-12 mx-auto">
        </div>
    </header>

    <div class="flex-1 px-4 sm:px-6 lg:px-8" style="padding-top: 5rem; display: flex; flex-direction: column;">
        <div class="max-w-7xl mx-auto pt-8 flex-grow">

            @if($errors->any())
                <div class="mb-8 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded max-w-6xl mx-auto">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $package = $packages->first();
            @endphp

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Billing & Add-ons -->
                <div class="lg:col-span-2 space-y-6">
                    @if($package)
                        <!-- Billing Period Card -->
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-bold text-gray-900">Your <span id="period-label">Monthly</span> Plan</h2>
                                <div class="flex items-center space-x-3">
                                    <span class="text-sm text-gray-600">Switch to year</span>
                                    <button type="button" id="billing-toggle" onclick="toggleBilling()" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none" style="background-color: #e5e7eb;">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-1" id="toggle-circle"></span>
                                    </button>
                                </div>
                            </div>

                            <p class="text-sm text-gray-600 mb-4">ONE PRICE. EVERYTHING INCLUDED.</p>
                            <div class="mb-2">
                                <span class="text-3xl font-bold text-gray-900" id="base-price">{{ $currencySymbol }}{{ number_format($package->price, 0) }}</span>
                                <span class="text-lg text-gray-600" id="period-text">/month</span>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">{{ $package->description }}</p>

                            @if($package->features)
                                <ul class="space-y-3">
                                    @foreach($package->features as $feature => $description)
                                        <li class="flex items-start">
                                            <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span class="text-gray-700 text-sm">{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <div id="savings-banner" class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                                <p class="text-sm text-green-700 font-semibold" id="savings-text">💰 Save 30% when you switch to yearly billing!</p>
                            </div>
                        </div>

                        <!-- Add-ons Card -->
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-6">Choose your Add-ons</h2>

                            @if($addons->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($addons as $addon)
                                        <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition">
                                            <div class="flex justify-between items-start mb-3">
                                                <div class="flex-1">
                                                    <h3 class="font-semibold text-gray-900 mb-1">{{ $addon->name }}</h3>
                                                    <p class="text-sm text-gray-600 mb-2">{{ $addon->description }}</p>
                                                    <p class="text-sm font-medium text-gray-900">{{ $currencySymbol }}{{ number_format($addon->price, 0) }}/month</p>
                                                </div>
                                                <button type="button" onclick="toggleAddon({{ $addon->id }}, {{ $addon->price }})"
                                                        id="addon-toggle-{{ $addon->id }}"
                                                        class="addon-toggle relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none ml-4"
                                                        style="background-color: #e5e7eb;"
                                                        data-addon-id="{{ $addon->id }}"
                                                        data-addon-price="{{ $addon->price }}"
                                                        data-addon-name="{{ $addon->name }}">
                                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-1"></span>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-center py-8">No add-ons available at this time.</p>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Right Column: Registration Form & Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-lg p-6 sticky top-24">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Order Summary</h2>

                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ $package->name ?? '' }}</span>
                                <span class="font-medium text-gray-900" id="summary-base">{{ $currencySymbol }}{{ number_format($package->price ?? 0, 0) }}</span>
                            </div>

                            <div id="yearly-discount-line" class="hidden justify-between text-sm">
                                <span class="text-green-600">Yearly discount (30%)</span>
                                <span class="font-medium text-green-600" id="discount-amount">-{{ $currencySymbol }}0</span>
                            </div>

                            <div id="addons-list" class="space-y-2">
                                <!-- Addons will be added here dynamically -->
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-gray-900">Total</span>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-gray-900" id="total-amount">{{ $currencySymbol }}{{ number_format($package->price ?? 0, 0) }}</div>
                                    <div class="text-sm text-gray-600" id="billing-period">/month</div>
                                </div>
                            </div>
                        </div>

                        <h2 class="text-lg font-bold text-gray-900 mb-4 border-t pt-6">Your Details</h2>

                <form action="{{ route('subscriptions.subscribe') }}" method="POST" id="registration-form">
                    @csrf
                    <input type="hidden" name="package_id" value="{{ $package->id ?? '' }}">
                    <input type="hidden" name="billing_period" id="billing_period_input" value="monthly">
                    <input type="hidden" name="selected_addons" id="selected_addons_input" value="">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" name="full_name" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                                style="focus:ring-color: #B98B69;"
                                value="{{ old('full_name') }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                                style="focus:ring-color: #B98B69;"
                                value="{{ old('email') }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                            <div class="flex gap-2">
                                <select name="country_code" required
                                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                                    style="focus:ring-color: #B98B69; width: 120px;">
                                    <option value="+1" {{ old('country_code') == '+1' ? 'selected' : '' }}>🇺🇸 +1</option>
                                    <option value="+44" {{ old('country_code') == '+44' ? 'selected' : '' }}>🇬🇧 +44</option>
                                    <option value="+961" {{ old('country_code') == '+961' ? 'selected' : '' }}>🇱🇧 +961</option>
                                    <option value="+971" {{ old('country_code') == '+971' ? 'selected' : '' }}>🇦🇪 +971</option>
                                    <option value="+966" {{ old('country_code') == '+966' ? 'selected' : '' }}>🇸🇦 +966</option>
                                    <option value="+20" {{ old('country_code') == '+20' ? 'selected' : '' }}>🇪🇬 +20</option>
                                    <option value="+962" {{ old('country_code') == '+962' ? 'selected' : '' }}>🇯🇴 +962</option>
                                    <option value="+965" {{ old('country_code') == '+965' ? 'selected' : '' }}>🇰🇼 +965</option>
                                </select>
                                <input type="tel" name="phone" required
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                                    style="focus:ring-color: #B98B69;"
                                    placeholder="12345678"
                                    value="{{ old('phone') }}">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
                            <input type="text" name="company_name" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                                style="focus:ring-color: #B98B69;"
                                value="{{ old('company_name') }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Website (Optional)</label>
                            <input type="url" name="website"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                                style="focus:ring-color: #B98B69;"
                                placeholder="https://example.com"
                                value="{{ old('website') }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                            <input type="password" name="password" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                                style="focus:ring-color: #B98B69;"
                                minlength="8">
                            <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                        </div>

                        <div class="pt-4">
                            <button type="submit"
                                class="w-full text-white py-3 px-4 rounded-lg transition font-semibold text-lg"
                                style="background-color: #615D40;"
                                onmouseover="this.style.backgroundColor='#7a7558'"
                                onmouseout="this.style.backgroundColor='#615D40'">
                                Complete Registration
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="bg-gray-900 text-white py-4 mt-auto" style="width: 100vw; position: relative; left: 50%; right: 50%; margin-left: -50vw; margin-right: -50vw;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-2 md:space-y-0 text-sm">
                <div class="text-center md:text-left">
                    <p class="text-gray-300">&copy; {{ date('Y') }} Powered by <a href="https://oliv.tech" target="_blank" class="transition" style="color: #B98B69;" onmouseover="this.style.color='#D3D2BC'" onmouseout="this.style.color='#B98B69'">Oliv.tech</a></p>
                </div>
                <div class="flex items-center space-x-2 text-gray-300">
                    <svg class="h-4 w-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <span class="text-xs">Secured payment powered by</span>
                    <svg class="h-5 w-auto" viewBox="0 0 60 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11.5 3.5C11.5 1.85 12.85 0.5 14.5 0.5H45.5C47.15 0.5 48.5 1.85 48.5 3.5V21.5C48.5 23.15 47.15 24.5 45.5 24.5H14.5C12.85 24.5 11.5 23.15 11.5 21.5V3.5Z" fill="#635BFF"/>
                        <path d="M22.8 17.2C21.4 17.2 20.3 16.5 19.9 15.3L21.4 14.6C21.6 15.3 22.2 15.8 22.9 15.8C23.7 15.8 24.2 15.3 24.2 14.3V8.5H25.8V14.3C25.8 16.2 24.7 17.2 22.8 17.2ZM27.5 17V8.5H29.1V11.1H29.2C29.5 10.3 30.2 9.8 31.1 9.8C32.3 9.8 33.2 10.6 33.2 12V17H31.6V12.4C31.6 11.6 31.1 11.2 30.4 11.2C29.6 11.2 29.1 11.7 29.1 12.6V17H27.5ZM35.2 17V8.5H36.8V11.1H36.9C37.2 10.3 37.9 9.8 38.8 9.8C40 9.8 40.9 10.6 40.9 12V17H39.3V12.4C39.3 11.6 38.8 11.2 38.1 11.2C37.3 11.2 36.8 11.7 36.8 12.6V17H35.2Z" fill="white"/>
                    </svg>
                    <span class="font-semibold text-xs">Stripe</span>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // State management
        let isYearly = false;
        const basePrice = {{ $package->price ?? 0 }};
        const currencySymbol = '{{ $currencySymbol }}';
        const selectedAddons = new Set();
        const addonPrices = {};
        const addonNames = {};

        // Initialize addon data
        @foreach($addons as $addon)
            addonPrices[{{ $addon->id }}] = {{ $addon->price }};
            addonNames[{{ $addon->id }}] = '{{ $addon->name }}';
        @endforeach

        function toggleBilling() {
            isYearly = !isYearly;
            const toggle = document.getElementById('billing-toggle');
            const circle = document.getElementById('toggle-circle');
            const periodLabel = document.getElementById('period-label');
            const periodText = document.getElementById('period-text');
            const savingsText = document.getElementById('savings-text');
            const discountLine = document.getElementById('yearly-discount-line');
            const billingInput = document.getElementById('billing_period_input');

            if (isYearly) {
                toggle.style.backgroundColor = '#615D40';
                circle.style.transform = 'translateX(1.25rem)';
                periodLabel.textContent = 'Yearly';
                periodText.textContent = '/year';
                savingsText.textContent = '🎉 You\'re saving 30% with yearly billing!';
                discountLine.classList.remove('hidden');
                discountLine.classList.add('flex');
                billingInput.value = 'yearly';
            } else {
                toggle.style.backgroundColor = '#e5e7eb';
                circle.style.transform = 'translateX(0.25rem)';
                periodLabel.textContent = 'Monthly';
                periodText.textContent = '/month';
                savingsText.textContent = '💰 Save 30% when you switch to yearly billing!';
                discountLine.classList.remove('flex');
                discountLine.classList.add('hidden');
                billingInput.value = 'monthly';
            }

            updatePricing();
        }

        function toggleAddon(addonId, addonPrice) {
            const toggle = document.getElementById('addon-toggle-' + addonId);
            const circle = toggle.querySelector('span');

            if (selectedAddons.has(addonId)) {
                selectedAddons.delete(addonId);
                toggle.style.backgroundColor = '#e5e7eb';
                circle.style.transform = 'translateX(0.25rem)';
            } else {
                selectedAddons.add(addonId);
                toggle.style.backgroundColor = '#615D40';
                circle.style.transform = 'translateX(1.25rem)';
            }

            updatePricing();
            updateSelectedAddonsInput();
        }

        function updatePricing() {
            let total = 0;
            let baseAmount = basePrice;

            // Calculate base subscription
            if (isYearly) {
                const yearlyFullPrice = basePrice * 12;
                const yearlyDiscountedPrice = yearlyFullPrice * 0.7; // 30% discount

                // Show full yearly price in left card
                document.getElementById('base-price').textContent = currencySymbol + Math.round(yearlyFullPrice);

                // Show full price in order summary
                document.getElementById('summary-base').textContent = currencySymbol + Math.round(yearlyFullPrice);

                // Calculate and show discount
                const discount = yearlyFullPrice * 0.3;
                document.getElementById('discount-amount').textContent = '-' + currencySymbol + Math.round(discount);

                // Total after discount
                total = yearlyDiscountedPrice;
            } else {
                document.getElementById('base-price').textContent = currencySymbol + Math.round(basePrice);
                document.getElementById('summary-base').textContent = currencySymbol + Math.round(basePrice);
                total = basePrice;
            }

            // Update addons list
            const addonsList = document.getElementById('addons-list');
            addonsList.innerHTML = '';

            selectedAddons.forEach(addonId => {
                const addonPrice = addonPrices[addonId];
                const addonName = addonNames[addonId];
                const addonTotal = isYearly ? addonPrice * 12 : addonPrice;

                total += addonTotal;

                const addonDiv = document.createElement('div');
                addonDiv.className = 'flex justify-between text-sm';
                addonDiv.innerHTML = `
                    <span class="text-gray-600">${addonName}</span>
                    <span class="font-medium text-gray-900">${currencySymbol}${Math.round(addonTotal)}</span>
                `;
                addonsList.appendChild(addonDiv);
            });

            // Update total
            document.getElementById('total-amount').textContent = currencySymbol + Math.round(total);
            document.getElementById('billing-period').textContent = isYearly ? '/year' : '/month';
        }

        function updateSelectedAddonsInput() {
            const addonsInput = document.getElementById('selected_addons_input');
            addonsInput.value = Array.from(selectedAddons).join(',');
        }

        // Initialize pricing on load
        updatePricing();
    </script>
</body>
</html>
