<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.auth', ['active' => 'login']);
    }

    public function showRegister(): View
    {
        return view('auth.auth', ['active' => 'signup']);
    }

    public function login(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Invalid email or password.'], 422);
            }

            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $redirectUrl = Auth::user()?->isStaffOrAdmin() ? route('admin.dashboard') : route('home');

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'redirectUrl' => $redirectUrl]);
        }

        return redirect()->to($redirectUrl);
    }

    public function register(RegisterRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name'         => $data['name'],
            'email'        => $data['email'],
            'phone_number' => $data['phone_number'],
            'password'     => Hash::make($data['password']),
            'account_type' => 'Customer',
            'role'         => 'user',
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $redirectUrl = $user->isAdmin() ? route('admin.dashboard') : route('home');

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'redirectUrl' => $redirectUrl]);
        }

        return redirect()->to($redirectUrl);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
