<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('user_id') || Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = trim($request->input('email'));
        $password = $request->input('password');

        $user = User::where('email', $email)->first();

        $valid = false;
        if ($user) {
            $hash = $user->password_hash ?? $user->password ?? '';
            if (password_verify($password, $hash) || Hash::check($password, $hash)) {
                $valid = true;
            }
        }

        if ($valid) {
            session([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_role' => $user->role ?? 'admin',
            ]);

            // Sincronizar también con sesión PHP nativa si es necesario para scripts legados
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_name'] = $user->name;
                $_SESSION['user_email'] = $user->email;
                $_SESSION['user_role'] = $user->role ?? 'admin';
            }

            try {
                Auth::login($user);
            } catch (\Throwable $e) {
                // Ignore if Auth driver differs
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Credenciales inválidas. Por favor verifique su correo y contraseña.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        try {
            Auth::logout();
        } catch (\Throwable $e) {}

        session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }

        return redirect()->route('login');
    }
}
