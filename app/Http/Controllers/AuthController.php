<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SavingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function loginForm(): View
    {
        return view('auth.form', ['mode' => 'login']);
    }

    public function registerForm(): View
    {
        return view('auth.form', ['mode' => 'register']);
    }

    public function forgotForm(): View
    {
        return view('auth.form', ['mode' => 'forgot']);
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.form', ['mode' => 'reset', 'token' => $token, 'email' => $request->query('email')]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt([...$data, 'is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])->onlyInput('email');
        }
        $request->session()->regenerate();

        if ($request->user()->role === 'admin') {
            $request->session()->forget('url.intended');

            return redirect()->route('filament.admin.pages.dashboard');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:255|unique:users', 'phone' => 'required|string|max:25', 'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)], 'terms' => 'accepted']);
        $user = User::create(collect($data)->except(['terms', 'password_confirmation'])->all());
        Auth::login($user);
        $request->session()->regenerate();
        SavingService::notify($user->id, 'welcome', 'Selamat datang!', 'Pilih produk impian dan mulai menabung hari ini.');

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function forgot(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        return back()->with('success', 'Jika email terdaftar, tautan reset telah dikirim.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)]]);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
        });

        return $status === Password::PASSWORD_RESET ? redirect()->route('login')->with('success', 'Kata sandi diperbarui. Silakan masuk.') : back()->withErrors(['email' => __($status)]);
    }
}
