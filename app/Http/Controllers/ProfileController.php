<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $avatarUrl = $user->avatar
            ? Storage::disk('public')->url($user->avatar)
            : null;

        return view('profile.edit', compact('user', 'avatarUrl'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $replaceAvatar = $request->hasFile('avatar');
        $removeAvatar = $request->boolean('remove_avatar');

        if (($replaceAvatar || $removeAvatar) && $user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
        }

        if ($replaceAvatar) {
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user->name = $validated['name'];
        $user->bio = $validated['bio'] ?? null;
        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
