<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Throwable;

class EnsureUserIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        // Permitir rutas de login, logout, diagnósticos debug y assets públicos
        if ($request->is('login') || $request->is('login.php') || $request->is('logout') || $request->is('logout.php') || $request->is('debug*') || $request->has('debug')) {
            return $next($request);
        }

        try {
            $hasLaravelSession = session()->has('user_id');
            $hasNativeSession = isset($_SESSION) && !empty($_SESSION['user_id']);
            $isAuth = false;
            try {
                $isAuth = Auth::check();
            } catch (Throwable $e) {}

            if (!$hasLaravelSession && !$hasNativeSession && !$isAuth) {
                return redirect()->route('login');
            }
        } catch (Throwable $e) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
