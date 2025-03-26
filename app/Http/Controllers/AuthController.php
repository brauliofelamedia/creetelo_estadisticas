<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeUser;
use App\Mail\ResetPassword;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            Mail::to(Auth::user()->email)->send(new WelcomeUser(Auth::user(),'password'));
            return redirect()->route('admin.index');
        }

        return redirect()->back()->with('error', 'Las credenciales proporcionadas son incorrectas.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function forgotForm()
    {
        return view('auth.forgot');
    }

    public function forgot(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return redirect()->back()->with('error', 'No hay concidencia con nuestros registros, revisa la dirección de correo.');
        }

        $token = Str::random(30);
        $expires = now()->addMinutes(10);

        $user->reset_password_token = $token;
        $user->reset_password_expires_at = $expires;

        $user->save();

        Mail::to($user->email)->send(new ResetPassword($user));

        return redirect()->back()->with('success', 'Se ha enviado un enlace para restablecer tu contraseña.');
    }

    public function magicLoginForm()
    {
        return view('auth.magic-login');
    }

    public function magicGenerateToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();
        //dd($user);

        if (!$user) {
            return redirect()->back()->with('error', 'El correo no concuerda con ninguno de nuestros registros.');
        }

        $token = Str::random(30);
        $expires = now()->addMinutes(5);

        $user->token_magic_login = $token;
        $user->token_magic_expires_at = $expires;
        $user->save();

        Http::post('https://n8n.webmaker.mx/webhook-test/a83a5330-1732-4cf5-885f-d03358097b7e', [
            'company' => Config::first(),
            'user' => $user,
            'type' => 'magic',
            'url' => route('magic.login.token', $token),
        ]);

        return redirect()->back()->with('success', 'Se ha enviado un enlace mágico a tu correo.');

    }

    public function magicLogin($token)
    {
        $user = User::where('token_magic_login', $token)
                    ->where('token_magic_expires_at', '>', now())
                    ->first();

        if (!$user) {
            return redirect()->route('magic.login')->with('error', 'El token proporcionado es inválido o ha expirado, te recomendamos generar otro.');
        }

        // Clear the magic token
        $user->token_magic_login = null;
        $user->token_magic_expires_at = null;
        $user->save();

        // Login the user
        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route('admin.index');
    }
}