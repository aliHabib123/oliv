<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class SubscriptionController extends Controller
{
    public function index()
    {
        $packages = Package::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $addons = \App\Models\Addon::where('is_active', true)->get();
        $currencySymbol = config('services.stripe.currency_symbol', 'AED');

        return view('subscriptions.index', compact('packages', 'addons', 'currencySymbol'));
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'country_code' => 'required|string|max:10',
            'phone' => 'required|string|max:20',
            'company_name' => 'required|string|max:255',
            'website' => 'nullable|url|max:255',
            'password' => 'required|string|min:8',
            'billing_period' => 'required|in:monthly,yearly',
            'selected_addons' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Create tenant with pending_payment status
            $tenant = Tenant::create([
                'name' => $validated['company_name'],
                'email' => $validated['email'],
                'phone' => $validated['country_code'] . $validated['phone'],
                'website' => $validated['website'],
                'status' => 'pending_payment',
            ]);

            // Create user linked to tenant
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Assign owner role to user
            $user->assignRole('owner');

            // Parse selected addons
            $selectedAddons = [];
            if (!empty($validated['selected_addons'])) {
                $selectedAddons = array_map('intval', explode(',', $validated['selected_addons']));
            }

            // Create subscription with pending status
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'package_id' => $validated['package_id'],
                'billing_period' => $validated['billing_period'],
                'selected_addons' => json_encode($selectedAddons),
                'status' => 'pending_payment',
                'subscribed_at' => now(),
            ]);

            DB::commit();

            // Redirect to payment page
            return redirect()->route('subscriptions.payment', [
                'tenant' => $tenant->id,
                'subscription' => $subscription->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create subscription: ' . $e->getMessage()]);
        }
    }

    public function payment($tenant, $subscription)
    {
        $tenant = Tenant::findOrFail($tenant);
        $subscription = Subscription::with('package')->findOrFail($subscription);

        // Verify subscription belongs to tenant and is pending
        if ($subscription->tenant_id != $tenant->id || $subscription->status != 'pending_payment') {
            abort(404);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Calculate pricing based on billing period
            $basePrice = $subscription->package->price;
            $interval = $subscription->billing_period === 'yearly' ? 'year' : 'month';

            // For yearly, calculate annual price with 30% discount
            if ($subscription->billing_period === 'yearly') {
                $unitAmount = round($basePrice * 12 * 0.7 * 100); // 30% discount on yearly
            } else {
                $unitAmount = $basePrice * 100;
            }

            // Build line items array starting with the subscription package
            $packageName = $subscription->package->name;
            $packageDescription = $subscription->package->description;

            if ($subscription->billing_period === 'yearly') {
                $packageName .= ' (Annual Subscription)';
                $packageDescription = 'Billed once per year. ' . $packageDescription;
            }

            $lineItems = [[
                'price_data' => [
                    'currency' => config('services.stripe.currency', 'aed'),
                    'product_data' => [
                        'name' => $packageName,
                        'description' => $packageDescription,
                    ],
                    'unit_amount' => $unitAmount,
                    'recurring' => [
                        'interval' => $interval,
                    ],
                ],
                'quantity' => 1,
            ]];

            // Add selected addons to line items
            $selectedAddons = json_decode($subscription->selected_addons, true) ?? [];
            if (!empty($selectedAddons)) {
                $addons = \App\Models\Addon::whereIn('id', $selectedAddons)->get();
                foreach ($addons as $addon) {
                    $addonAmount = $subscription->billing_period === 'yearly'
                        ? $addon->price * 12 * 100
                        : $addon->price * 100;

                    $addonName = $addon->name;
                    $addonDescription = $addon->description;

                    if ($subscription->billing_period === 'yearly') {
                        $addonName .= ' (Annual)';
                        $addonDescription = 'Billed annually. ' . $addonDescription;
                    }

                    $lineItems[] = [
                        'price_data' => [
                            'currency' => config('services.stripe.currency', 'aed'),
                            'product_data' => [
                                'name' => $addonName,
                                'description' => $addonDescription,
                            ],
                            'unit_amount' => $addonAmount,
                            'recurring' => [
                                'interval' => $interval,
                            ],
                        ],
                        'quantity' => 1,
                    ];
                }
            }

            // Create Stripe Checkout Session
            $checkoutSession = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'subscription',
                'success_url' => route('subscriptions.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('subscriptions.payment', ['tenant' => $tenant->id, 'subscription' => $subscription->id]),
                'client_reference_id' => $subscription->id,
                'customer_email' => $tenant->email,
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'tenant_id' => $tenant->id,
                    'billing_period' => $subscription->billing_period,
                ],
            ]);

            return view('subscriptions.payment', [
                'tenant' => $tenant,
                'subscription' => $subscription,
                'checkoutSessionId' => $checkoutSession->id,
                'stripePublicKey' => config('services.stripe.key'),
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create payment session: ' . $e->getMessage()]);
        }
    }

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('subscriptions.index');
        }

        return view('subscriptions.success', [
            'sessionId' => $sessionId,
        ]);
    }

    public function webhook(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $endpointSecret = config('services.stripe.webhook_secret');

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Webhook signature verification failed'], 400);
        }

        // Handle the event based on type
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($event);
                break;

            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($event);
                break;

            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event);
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event);
                break;

            default:
                Log::info('Unhandled webhook event', ['type' => $event->type]);
        }

        return response()->json(['status' => 'success']);
    }

    private function handleCheckoutCompleted($event)
    {
        $session = $event->data->object;
        $subscriptionId = $session->metadata->subscription_id ?? null;
        $tenantId = $session->metadata->tenant_id ?? null;

        Log::info('Webhook: Checkout completed', [
            'subscription_id' => $subscriptionId,
            'tenant_id' => $tenantId,
            'stripe_subscription_id' => $session->subscription ?? 'NULL',
        ]);

        if (!$subscriptionId || !$tenantId) {
            Log::error('Missing metadata in checkout webhook', ['metadata' => $session->metadata]);
            return;
        }

        try {
            DB::beginTransaction();

            $subscription = Subscription::findOrFail($subscriptionId);
            $subscription->update([
                'status' => 'active',
                'stripe_subscription_id' => $session->subscription,
            ]);

            $tenant = Tenant::findOrFail($tenantId);
            $tenant->update(['status' => 'active']);

            DB::commit();

            Log::info('Subscription activated', [
                'subscription_id' => $subscriptionId,
                'stripe_subscription_id' => $session->subscription,
            ]);

            // TODO: Send welcome email
            $user = User::where('tenant_id', $tenantId)->where('email', $tenant->email)->first();
            if ($user) {
                // Mail::to($user->email)->send(new WelcomeEmail($user, $tenant));
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout webhook failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function handlePaymentSucceeded($event)
    {
        $invoice = $event->data->object;
        $stripeSubscriptionId = $invoice->subscription;

        Log::info('Webhook: Payment succeeded', ['stripe_subscription_id' => $stripeSubscriptionId]);

        try {
            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

            if (!$subscription) {
                Log::warning('Subscription not found for payment success', ['stripe_subscription_id' => $stripeSubscriptionId]);
                return;
            }

            // Reset any failed payment attempts and ensure subscription is active
            $subscription->update(['status' => 'active']);
            $subscription->tenant->update(['status' => 'active']);

            Log::info('Subscription payment successful', ['subscription_id' => $subscription->id]);

            // TODO: Send receipt email
            // Mail::to($subscription->tenant->email)->send(new PaymentReceiptEmail($subscription, $invoice));

        } catch (\Exception $e) {
            Log::error('Payment success webhook failed', ['error' => $e->getMessage()]);
        }
    }

    private function handlePaymentFailed($event)
    {
        $invoice = $event->data->object;
        $stripeSubscriptionId = $invoice->subscription;
        $attemptCount = $invoice->attempt_count;

        Log::warning('Webhook: Payment failed', [
            'stripe_subscription_id' => $stripeSubscriptionId,
            'attempt_count' => $attemptCount,
        ]);

        try {
            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

            if (!$subscription) {
                Log::warning('Subscription not found for payment failure', ['stripe_subscription_id' => $stripeSubscriptionId]);
                return;
            }

            // Update subscription status to past_due
            $subscription->update(['status' => 'past_due']);
            $subscription->tenant->update(['status' => 'past_due']);

            Log::info('Subscription marked as past_due', [
                'subscription_id' => $subscription->id,
                'attempt_count' => $attemptCount,
            ]);

            // Send payment failure email to customer
            $user = User::where('tenant_id', $subscription->tenant_id)
                ->where('email', $subscription->tenant->email)
                ->first();

            if ($user) {
                // TODO: Send payment failure email
                // Mail::to($user->email)->send(new PaymentFailedEmail($subscription, $attemptCount));
                Log::info('Payment failure email should be sent', [
                    'email' => $user->email,
                    'attempt' => $attemptCount,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Payment failure webhook failed', ['error' => $e->getMessage()]);
        }
    }

    private function handleSubscriptionUpdated($event)
    {
        $stripeSubscription = $event->data->object;
        $stripeSubscriptionId = $stripeSubscription->id;
        $status = $stripeSubscription->status;

        Log::info('Webhook: Subscription updated', [
            'stripe_subscription_id' => $stripeSubscriptionId,
            'status' => $status,
        ]);

        try {
            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

            if (!$subscription) {
                Log::warning('Subscription not found for update', ['stripe_subscription_id' => $stripeSubscriptionId]);
                return;
            }

            // Map Stripe status to our status
            $ourStatus = match($status) {
                'active' => 'active',
                'past_due' => 'past_due',
                'canceled' => 'cancelled',
                'unpaid' => 'cancelled',
                default => $subscription->status,
            };

            $subscription->update(['status' => $ourStatus]);
            $subscription->tenant->update(['status' => $ourStatus]);

            Log::info('Subscription status updated', [
                'subscription_id' => $subscription->id,
                'new_status' => $ourStatus,
            ]);

        } catch (\Exception $e) {
            Log::error('Subscription update webhook failed', ['error' => $e->getMessage()]);
        }
    }

    private function handleSubscriptionDeleted($event)
    {
        $stripeSubscription = $event->data->object;
        $stripeSubscriptionId = $stripeSubscription->id;

        Log::warning('Webhook: Subscription deleted/cancelled', ['stripe_subscription_id' => $stripeSubscriptionId]);

        try {
            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

            if (!$subscription) {
                Log::warning('Subscription not found for deletion', ['stripe_subscription_id' => $stripeSubscriptionId]);
                return;
            }

            // Mark subscription as cancelled
            $subscription->update(['status' => 'cancelled']);
            $subscription->tenant->update(['status' => 'cancelled']);

            Log::info('Subscription cancelled', ['subscription_id' => $subscription->id]);

            // TODO: Send cancellation confirmation email
            $user = User::where('tenant_id', $subscription->tenant_id)
                ->where('email', $subscription->tenant->email)
                ->first();

            if ($user) {
                // Mail::to($user->email)->send(new SubscriptionCancelledEmail($subscription));
                Log::info('Cancellation email should be sent', ['email' => $user->email]);
            }

        } catch (\Exception $e) {
            Log::error('Subscription deletion webhook failed', ['error' => $e->getMessage()]);
        }
    }
}
