<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's Alpaca credentials.
     */
    public function updateAlpacaCredentials(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'alpaca_key_id' => ['nullable', 'string', 'max:255'],
            'alpaca_secret' => ['nullable', 'string', 'max:255'],
            'alpaca_is_paper' => ['sometimes', 'boolean'],
        ]);

        // Build update data - only include fields that are present AND not empty
        $updateData = [];

        // Only update key_id if it's provided and not empty
        if (isset($validated['alpaca_key_id']) && !empty($validated['alpaca_key_id'])) {
            $updateData['alpaca_key_id'] = $validated['alpaca_key_id'];
        }

        // Only update secret if it's provided and not empty
        if (isset($validated['alpaca_secret']) && !empty($validated['alpaca_secret'])) {
            $updateData['alpaca_secret'] = $validated['alpaca_secret'];
        }

        // Always update trading mode if provided (can be true/false)
        if (isset($validated['alpaca_is_paper'])) {
            $updateData['alpaca_is_paper'] = $validated['alpaca_is_paper'];
        }

        if (!empty($updateData)) {
            $request->user()->update($updateData);
        }

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
