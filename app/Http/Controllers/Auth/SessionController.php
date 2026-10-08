<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\RegistrationService;
use App\Support\DomainException;
use App\Support\HomeRedirect;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($data['login']);
        $credentials = str_contains($login, '@')
            ? ['email' => mb_strtolower($login), 'password' => $data['password']]
            : ['phone' => $this->phone($login), 'password' => $data['password']];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput()->with('error', 'Téléphone ou mot de passe incorrect.');
        }

        $request->session()->regenerate();

        if (! $request->user()->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->with('error', 'Ce compte est suspendu.');
        }

        return HomeRedirect::for($request->user());
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function createLandlord()
    {
        return view('auth.register-landlord');
    }

    public function storeLandlord(Request $request, RegistrationService $registration)
    {
        $request->merge(['phone' => $this->phone((string) $request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'organization_name' => ['required', 'string', 'max:160'],
        ]);

        $user = $registration->registerLandlord(
            $data['name'],
            $data['phone'],
            $data['email'] ?? null,
            $data['password'],
            $data['organization_name'],
        );

        Auth::login($user);
        $request->session()->regenerate();

        return HomeRedirect::for($user);
    }

    public function createTenant()
    {
        return view('auth.register-tenant');
    }

    public function storeTenant(Request $request, RegistrationService $registration)
    {
        $request->merge(['phone' => $this->phone((string) $request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $registration->registerTenant($data['name'], $data['phone'], $data['email'] ?? null, $data['password']);
        Auth::login($user);
        $request->session()->regenerate();

        return HomeRedirect::for($user);
    }

    private function phone(string $value): string
    {
        try {
            return Phone::normalize($value);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['phone' => $exception->getMessage(), 'login' => $exception->getMessage()]);
        }
    }
}
