<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $limiteur = 'login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($limiteur, 5)) {
            $secondes = RateLimiter::availableIn($limiteur);
            throw ValidationException::withMessages([
                'email' => "Trop de tentatives. Réessayez dans {$secondes} secondes.",
            ]);
        }

        try {
            $user = $this->authService->authenticate($credentials['email'], $credentials['password']);
        } catch (ValidationException $e) {
            RateLimiter::hit($limiteur, 60);
            throw $e;
        }

        RateLimiter::clear($limiteur);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $this->authService->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
