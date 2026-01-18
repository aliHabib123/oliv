<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\TenantAddon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantAddonController extends Controller
{
    public function index()
    {
        $tenant = Auth::user()->tenant ?? null;

        if (!$tenant) {
            return redirect()->route('filament.admin.pages.dashboard')
                ->with('error', 'No tenant associated with your account');
        }

        $addons = Addon::where('is_active', true)->get();
        $activeAddons = TenantAddon::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->pluck('addon_id')
            ->toArray();

        return view('tenant-addons.index', compact('addons', 'activeAddons', 'tenant'));
    }

    public function subscribe(Request $request, Addon $addon)
    {
        $tenant = Auth::user()->tenant ?? null;

        if (!$tenant) {
            return back()->with('error', 'No tenant associated with your account');
        }

        // Check if already subscribed
        $existing = TenantAddon::where('tenant_id', $tenant->id)
            ->where('addon_id', $addon->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return back()->with('error', 'Already subscribed to this add-on');
        }

        // Create subscription (dummy payment)
        TenantAddon::create([
            'tenant_id' => $tenant->id,
            'addon_id' => $addon->id,
            'status' => 'active',
            'subscribed_at' => now(),
        ]);

        return back()->with('success', 'Successfully subscribed to ' . $addon->name);
    }

    public function unsubscribe(Request $request, Addon $addon)
    {
        $tenant = Auth::user()->tenant ?? null;

        if (!$tenant) {
            return back()->with('error', 'No tenant associated with your account');
        }

        TenantAddon::where('tenant_id', $tenant->id)
            ->where('addon_id', $addon->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        return back()->with('success', 'Successfully unsubscribed from ' . $addon->name);
    }
}
