<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'account_type' => ['nullable', Rule::in([Role::ROLE_STUDENT, Role::ROLE_INSTRUCTOR])],
        ]);

        // Never trust a public `role` or `role_id` request field. The only public
        // choice is the constrained account_type selector; omitted values retain
        // the legacy-safe default of creating an active student account.
        $accountType = $validated['account_type'] ?? Role::ROLE_STUDENT;
        $isInstructorRegistration = $accountType === Role::ROLE_INSTRUCTOR;
        $roleModel = Role::where('name', $accountType)->firstOrFail();

        $user = User::create([
            'role_id' => $roleModel->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => ! $isInstructorRegistration,
        ]);

        if ($isInstructorRegistration) {
            return redirect()
                ->route('login')
                ->with('success', 'Pendaftaran akun guru berhasil. Akun Anda menunggu persetujuan Admin sebelum dapat digunakan.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('student.dashboard')->with('success', 'Selamat datang! Akun Anda telah berhasil dibuat.');
    }
}
