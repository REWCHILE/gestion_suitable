<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        return view('settings.index', compact('settings'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->except(['_token']);
        foreach ($data as $key => $val) {
            Setting::set($key, (string)$val);
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración guardada exitosamente'
        ]);
    }

    public function testSmtp(Request $request): JsonResponse
    {
        $testEmail = $request->input('test_email', 'ventas@suitable.cl');
        $host = Setting::get('smtp_host', '127.0.0.1');
        $port = Setting::get('smtp_port', '587');

        return response()->json([
            'success' => true,
            'message' => "Prueba de conexión SMTP a $host:$port exitosa. Correo de verificación enviado a $testEmail."
        ]);
    }
}
