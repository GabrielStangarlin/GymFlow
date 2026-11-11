<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt([...$credentials, 'active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Credenciais inválidas ou conta inativa.']);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->homeFor(Auth::user()->role));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function home(): RedirectResponse
    {
        return redirect($this->homeFor(Auth::user()->role));
    }

    private function homeFor(Role $role): string
    {
        return $role === Role::Client ? route('client.qr') : route('crm.dashboard');
    }
}
