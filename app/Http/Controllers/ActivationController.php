<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivateAccountRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ActivationController extends Controller
{
    public function show(Request $request, string $token): View
    {
        return view('auth.activate', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function store(ActivateAccountRequest $request): RedirectResponse
    {
        $activatedUser = null;

        $status = Password::broker('activations')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use (&$activatedUser): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $activatedUser = $user;
            },
        );

        if ($status !== Password::PASSWORD_RESET || $activatedUser === null) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'A ligação de ativação é inválida ou expirou. Contacte a receção.',
            ]);
        }

        Auth::login($activatedUser);
        $request->session()->regenerate();

        return redirect()->route('client.qr');
    }
}
