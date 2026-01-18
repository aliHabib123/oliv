<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function index()
    {
        $packages = Package::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('subscriptions.index', compact('packages'));
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'contact_person' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            DB::beginTransaction();

            // Generate random password
            $password = Str::random(12);

            // Create tenant first
            $tenant = Tenant::create([
                'name' => $validated['company_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ]);

            // Create user linked to tenant
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['contact_person'],
                'email' => $validated['email'],
                'password' => Hash::make($password),
            ]);

            // Assign owner role to user
            $user->assignRole('owner');

            // Create subscription
            Subscription::create([
                'tenant_id' => $tenant->id,
                'package_id' => $validated['package_id'],
                'status' => 'active',
                'subscribed_at' => now(),
            ]);

            DB::commit();

            // In production, send credentials via email
            // For now, display on success page
            return view('subscriptions.success', [
                'email' => $validated['email'],
                'password' => $password,
                'tenant' => $tenant,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create subscription: ' . $e->getMessage()]);
        }
    }
}
