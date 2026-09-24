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

        // Evaluar estado inicial para cada proveedor
        $statuses = [
            'gemini' => $this->verifyGemini($settings['gemini_api_key'] ?? ''),
            'groq' => $this->verifyGroq($settings['groq_api_key'] ?? ''),
            'openai' => $this->verifyOpenAI($settings['openai_api_key'] ?? ''),
            'claude' => $this->verifyClaude($settings['claude_api_key'] ?? ''),
            'smtp' => $this->verifySmtp($settings['smtp_host'] ?? '', $settings['smtp_port'] ?? '587'),
        ];

        return view('settings.index', compact('settings', 'statuses'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->except(['_token']);
        foreach ($data as $key => $val) {
            Setting::set($key, (string)$val);
        }

        // Re-verificar estados tras guardar
        $statuses = [
            'gemini' => $this->verifyGemini($data['gemini_api_key'] ?? Setting::get('gemini_api_key')),
            'groq' => $this->verifyGroq($data['groq_api_key'] ?? Setting::get('groq_api_key')),
            'openai' => $this->verifyOpenAI($data['openai_api_key'] ?? Setting::get('openai_api_key')),
            'claude' => $this->verifyClaude($data['claude_api_key'] ?? Setting::get('claude_api_key')),
            'smtp' => $this->verifySmtp($data['smtp_host'] ?? Setting::get('smtp_host', '127.0.0.1'), $data['smtp_port'] ?? Setting::get('smtp_port', '587')),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Configuración guardada exitosamente',
            'statuses' => $statuses
        ]);
    }

    public function checkStatus(Request $request): JsonResponse
    {
        $provider = $request->input('provider', 'all');

        $geminiKey = $request->input('gemini_api_key', Setting::get('gemini_api_key'));
        $groqKey = $request->input('groq_api_key', Setting::get('groq_api_key'));
        $openaiKey = $request->input('openai_api_key', Setting::get('openai_api_key'));
        $claudeKey = $request->input('claude_api_key', Setting::get('claude_api_key'));
        $smtpHost = $request->input('smtp_host', Setting::get('smtp_host', '127.0.0.1'));
        $smtpPort = $request->input('smtp_port', Setting::get('smtp_port', '587'));

        $statuses = [
            'gemini' => $this->verifyGemini($geminiKey),
            'groq' => $this->verifyGroq($groqKey),
            'openai' => $this->verifyOpenAI($openaiKey),
            'claude' => $this->verifyClaude($claudeKey),
            'smtp' => $this->verifySmtp($smtpHost, $smtpPort),
        ];

        if ($provider !== 'all' && isset($statuses[$provider])) {
            return response()->json([
                'success' => true,
                'provider' => $provider,
                'status' => $statuses[$provider]
            ]);
        }

        return response()->json([
            'success' => true,
            'statuses' => $statuses
        ]);
    }

    public function testSmtp(Request $request): JsonResponse
    {
        $testEmail = $request->input('test_email', 'ventas@suitable.cl');
        $host = $request->input('smtp_host', Setting::get('smtp_host', '127.0.0.1'));
        $port = $request->input('smtp_port', Setting::get('smtp_port', '587'));

        $verify = $this->verifySmtp($host, $port);

        if ($verify['status'] === 'active') {
            return response()->json([
                'success' => true,
                'message' => "Prueba de conexión SMTP a $host:$port exitosa. Servidor alcanzable para envíos a $testEmail.",
                'verify' => $verify
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "Fallo de conexión SMTP a $host:$port. " . $verify['message'],
            'verify' => $verify
        ], 400);
    }

    public function aiBrandAssist(Request $request): JsonResponse
    {
        $focus = trim($request->input('focus', ''));
        $currentName = trim($request->input('current_name', 'Suitable Fabrica Uniformes Clínicos'));
        $provider = Setting::get('active_ai_provider', 'groq');

        $systemPrompt = "Eres un estratega senior en Email Marketing B2B, entregabilidad y psicología de compras para el sector salud en Chile. Trabajas para 'Suitable' (suitable.cl), fabricante chileno de uniformes clínicos y scrubs con tecnología Flex 4-Way y 6 meses de garantía.
Tu objetivo es sugerir nombres de remitente (Sender Name) y correos de contacto optimizados para que directores médicos, jefes de adquisiciones y enfermeras jefes de clínicas y hospitales en Chile abran los correos y confíen en la propuesta.
Genera SIEMPRE un JSON válido con la siguiente estructura exacta:
{
  \"recommendations\": [
    {
      \"sender_name\": \"Nombre sugerido\",
      \"sender_email\": \"correo@suitable.cl\",
      \"type\": \"Personal + Empresa | Fábrica Directa | Servicio & Tallaje | Institucional\",
      \"strategy\": \"Por qué esta fórmula funciona en Chile (máx 2 líneas)\",
      \"open_rate_boost\": \"+35% apertura\"
    }
  ]
}";

        $prompt = "El usuario actualmente tiene como remitente: '$currentName'. " .
            ($focus ? "Desea enfocar la redacción en: '$focus'. " : "Genera las mejores opciones de alto impacto B2B para clínicas, hospitales y centros dentales en Chile.") .
            "Genera 4 opciones profesionales listas para aplicar.";

        try {
            $result = \App\Services\AiService::generateCopy($provider, $prompt, $systemPrompt);
            $raw = $result['content'] ?? $result['copy'] ?? $result['raw'] ?? '';

            if (preg_match('/\{[\s\S]*\}/', $raw, $m)) {
                $decoded = json_decode($m[0], true);
                if (isset($decoded['recommendations']) && is_array($decoded['recommendations']) && count($decoded['recommendations']) > 0) {
                    return response()->json([
                        'success' => true,
                        'provider' => strtoupper($provider),
                        'recommendations' => $decoded['recommendations']
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Continuar al fallback curado
        }

        $fallback = [
            [
                'sender_name' => 'Javier de Suitable | Convenios Clínicos',
                'sender_email' => 'convenios@suitable.cl',
                'type' => 'Personal + Empresa (Mayor Tasa de Apertura)',
                'strategy' => 'Combina cercanía humana con respaldo de marca. Evita parecer publicidad masiva y aumenta hasta un 38% la tasa de apertura.',
                'open_rate_boost' => '+38% apertura'
            ],
            [
                'sender_name' => 'Fábrica Suitable | Uniformes Médicos',
                'sender_email' => 'ventas@suitable.cl',
                'type' => 'Fábrica Directa (Ahorro Institucional)',
                'strategy' => 'Comunica de inmediato que es confección 100% chilena sin intermediarios, punto clave para encargados de presupuesto.',
                'open_rate_boost' => '+31% apertura'
            ],
            [
                'sender_name' => 'Suitable Chile | Tallaje en Terreno',
                'sender_email' => 'tallaje@suitable.cl',
                'type' => 'Servicio Exclusivo (Diferenciador)',
                'strategy' => 'Pone el foco en el servicio presencial de llevar muestras y tallero a la clínica, resolviendo el principal temor de calces.',
                'open_rate_boost' => '+35% apertura'
            ],
            [
                'sender_name' => 'Dotaciones Clínicas Suitable',
                'sender_email' => 'dotaciones@suitable.cl',
                'type' => 'Corporativo / Licitaciones B2B',
                'strategy' => 'Formato formal óptimo para licitaciones públicas, convenios semestrales y compras masivas de hospitales.',
                'open_rate_boost' => '+26% apertura'
            ]
        ];

        return response()->json([
            'success' => true,
            'provider' => strtoupper($provider),
            'recommendations' => $fallback
        ]);
    }

    private function verifyGroq(?string $apiKey): array
    {
        $apiKey = trim((string)$apiKey);
        if (empty($apiKey)) {
            return ['status' => 'error', 'label' => 'Sin configurar', 'message' => 'Falta ingresar API Key de Groq'];
        }

        $ch = curl_init('https://api.groq.com/openai/v1/models');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $apiKey"]
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            return ['status' => 'active', 'label' => 'Activo', 'message' => 'Conexión verificada con Groq (Llama 3.3)'];
        }

        $json = json_decode($res, true);
        $msg = $json['error']['message'] ?? "Error HTTP $code (Clave inválida)";
        return ['status' => 'error', 'label' => 'Error', 'message' => $msg];
    }

    private function verifyGemini(?string $apiKey): array
    {
        $apiKey = trim((string)$apiKey);
        if (empty($apiKey)) {
            return ['status' => 'error', 'label' => 'Sin configurar', 'message' => 'Falta ingresar API Key de Gemini'];
        }

        $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            return ['status' => 'active', 'label' => 'Activo', 'message' => 'Conexión verificada con Google Gemini'];
        }

        $json = json_decode($res, true);
        $msg = $json['error']['message'] ?? "Error HTTP $code (Clave inválida)";
        return ['status' => 'error', 'label' => 'Error', 'message' => $msg];
    }

    private function verifyOpenAI(?string $apiKey): array
    {
        $apiKey = trim((string)$apiKey);
        if (empty($apiKey)) {
            return ['status' => 'error', 'label' => 'Sin configurar', 'message' => 'Falta ingresar API Key de OpenAI'];
        }

        $ch = curl_init('https://api.openai.com/v1/models');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $apiKey"]
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            return ['status' => 'active', 'label' => 'Activo', 'message' => 'Conexión verificada con OpenAI (ChatGPT)'];
        }

        $json = json_decode($res, true);
        $msg = $json['error']['message'] ?? "Error HTTP $code (Clave inválida)";
        return ['status' => 'error', 'label' => 'Error', 'message' => $msg];
    }

    private function verifyClaude(?string $apiKey): array
    {
        $apiKey = trim((string)$apiKey);
        if (empty($apiKey)) {
            return ['status' => 'error', 'label' => 'Sin configurar', 'message' => 'Falta ingresar API Key de Claude'];
        }

        $ch = curl_init('https://api.anthropic.com/v1/models');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_HTTPHEADER => [
                "x-api-key: $apiKey",
                "anthropic-version: 2023-06-01"
            ]
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            return ['status' => 'active', 'label' => 'Activo', 'message' => 'Conexión verificada con Anthropic Claude'];
        }

        $json = json_decode($res, true);
        $msg = $json['error']['message'] ?? "Error HTTP $code (Clave inválida)";
        return ['status' => 'error', 'label' => 'Error', 'message' => $msg];
    }

    private function verifySmtp(?string $host, ?string $port): array
    {
        $host = trim((string)$host);
        $port = (int)($port ?: 587);

        if (empty($host)) {
            return ['status' => 'error', 'label' => 'Sin configurar', 'message' => 'Host SMTP no definido'];
        }

        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, 1.2);
        if ($fp) {
            fclose($fp);
            return ['status' => 'active', 'label' => 'Activo', 'message' => "Puerto $port alcanzable en $host"];
        }

        return ['status' => 'error', 'label' => 'Error', 'message' => "Conexión rechazada a $host:$port ($errstr)"];
    }
}
