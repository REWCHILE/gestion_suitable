<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        // Permitir rutas de login, logout y assets públicos
        if ($request->is('login') || $request->is('login.php') || $request->is('logout') || $request->is('logout.php')) {
            return $next($request);
        }

        // Verificar sesión Laravel o sesión PHP
        $hasLaravelSession = session()->has('user_id');
        $hasNativeSession = !empty($_SESSION['user_id']);

        if (!$hasLaravelSession && !$hasNativeSession && !\Illuminate\Support\Facades\Auth::check()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
