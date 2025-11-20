<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Index');
    }

    public function updateServices(Request $request)
    {
        $validated = $request->validate([
            'enabled_services' => ['required', 'array'],
            'enabled_services.*' => ['string', 'in:payment_links,orders,agent'],
        ]);

        $request->user()->update([
            'enabled_services' => $validated['enabled_services'],
        ]);

        return back()->with('success', 'Services updated successfully.');
    }

    public function updateRazorpay(Request $request)
    {
        $validated = $request->validate([
            'razorpay_key_id' => ['required', 'string', 'max:255'],
            'razorpay_key_secret' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->update($validated);

        return back()->with('success', 'Razorpay credentials updated successfully.');
    }

    public function updateAI(Request $request)
    {
        $validated = $request->validate([
            'ai_provider' => ['required', 'string', 'in:anthropic,openai'],
            'anthropic_api_key' => ['nullable', 'string', 'max:255'],
            'openai_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        $updateData = ['ai_provider' => $validated['ai_provider']];

        if (! empty($validated['anthropic_api_key'])) {
            $updateData['anthropic_api_key'] = $validated['anthropic_api_key'];
        }

        if (! empty($validated['openai_api_key'])) {
            $updateData['openai_api_key'] = $validated['openai_api_key'];
        }

        $request->user()->update($updateData);

        return back()->with('success', 'AI settings updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}
