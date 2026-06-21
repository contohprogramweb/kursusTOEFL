<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UpdatePreferencesRequest;
use App\Models\UserProfile;
use App\Models\UserPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $profile = $user->profile;
        $preference = $user->preference;

        return view('profile.edit', [
            'user' => $user,
            'profile' => $profile,
            'preference' => $preference,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        // Get or create user profile
        $profile = UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        // Check authorization using Policy
        $this->authorize('update', $profile);

        $validated = $request->validated();

        // Handle avatar upload and resize
        if ($request->hasFile('avatar')) {
            $avatarPath = $this->processAvatar($request->file('avatar'), $user->id);
            $validated['avatar_url'] = $avatarPath;
        }

        // Update profile fields
        $profile->fill(array_merge($validated, [
            'full_name' => $validated['full_name'] ?? $profile->full_name,
        ]));

        // Remove full_name from validated as it's not in user_profiles table
        unset($validated['full_name']);
        unset($validated['avatar']);

        $profile->save();

        // Also update user's full_name
        if (isset($request->validated()['full_name'])) {
            $user->full_name = $request->validated()['full_name'];
            $user->save();
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's display and notification preferences.
     */
    public function updatePreferences(UpdatePreferencesRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        // Get or create user preference
        $preference = UserPreference::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        // Check authorization using Policy
        $this->authorize('update', $preference);

        $validated = $request->validated();

        // Update preference fields
        $preference->fill($validated);
        $preference->save();

        return Redirect::route('profile.edit')->with('status', 'preferences-updated');
    }

    /**
     * Process and resize avatar image.
     */
    private function processAvatar($file, int $userId): string
    {
        // Generate unique filename
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = 'avatars/' . $filename;

        // Read image
        $image = Image::read($file->getRealPath());

        // Resize to 400x400 with crop (cover)
        $image->cover(400, 400);

        // Save to storage
        $image->save(storage_path('app/public/' . $path));

        return Storage::url($path);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
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
