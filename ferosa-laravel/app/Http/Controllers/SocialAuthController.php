<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    private const ALLOWED = ['google', 'facebook'];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::ALLOWED), 404);

        $clientId = config("services.{$provider}.client_id");
        if (empty($clientId)) {
            return redirect()->route('login')
                ->withErrors(['email' => ucfirst($provider) . ' login is not configured yet. Please use email instead.']);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::ALLOWED), 404);

        try {
            $social = Socialite::driver($provider)->user();
        } catch (Throwable) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Social login failed. Please try again.']);
        }

        $idColumn = $provider . '_id';

        // Find by provider ID first, then fall back to email
        $user = User::where($idColumn, $social->getId())->first()
            ?? User::where('email', $social->getEmail())->first();

        if ($user) {
            // Link provider ID if not already stored
            if (! $user->$idColumn) {
                $user->update([$idColumn => $social->getId()]);
            }
        } else {
            $user = User::create([
                'name'         => $social->getName() ?? $social->getNickname() ?? 'User',
                'email'        => $social->getEmail(),
                'password'     => null,
                'account_type' => 'Customer',
                'role'         => 'user',
                $idColumn      => $social->getId(),
            ]);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        $redirectUrl = $user->isStaffOrAdmin()
            ? route('admin.dashboard')
            : route('home');

        return redirect()->to($redirectUrl);
    }
}
