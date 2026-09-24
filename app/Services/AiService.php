<?php

namespace App\Services;

use App\Models\Setting;
use Exception;

class AiService
{
    public static function getAvailableProviders(): array
    {
        return [
            'groq' => [
                'name' => 'Groq (Llama 3.3)',
                'badge' => 'Ultra Rápido',
                'models' => ['llama-3.3-70b-versatile', 'llama-3.1-8b-instant', 'mixtral-8x7b-32768'],
                'default_model' => 'llama-3.3-70b-versatile',
                'icon' => '⚡',
            ],
            'openai' => [
                'name' => 'OpenAI (ChatGPT)',
                'badge' => 'Alta Precisión',
                'models' => ['gpt-4o-mini', 'gpt-4o', 'gpt-3.5-turbo'],
                'default_model' => 'gpt-4o-mini',
                'icon' => '🧠',
            ],
            'claude' => [
                'name' => 'Anthropic (Claude 3.5)',
                'badge' => 'Redacción Creativa',
                'models' => ['claude-3-5-sonnet-20241022', 'claude-3-haiku-20240307'],
                'default_model' => 'claude-3-5-sonnet-20241022',
                'icon' => '🎭',
            ],
            'gemini' => [
                'name' => 'Google (Gemini)',
                'badge' => 'Multimodal & Rápido',
                'models' => ['gemini-1.5-flash', 'gemini-2.0-flash', 'gemini-1.5-pro'],
                'default_model' => 'gemini-1.5-flash',
                'icon' => '✨',
            ],
        ];
    }

    public static function generateCopy(string $provider, string $prompt, string $systemPrompt = '', string $model = ''): array
    {
        $startTime = microtime(true);
        $provider = strtolower(trim($provider));

        if (!$systemPrompt) {
            $systemPrompt = "Eres el estratega senior de Email Marketing B2B de 'SUITABLE' (suitable.cl), fabricante chileno de uniformes clínicos de alta gama. Tus propuestas de valor obligatorias son: 1) Fabricación 100% chilena sin intermediarios, 2) Telas antifluidos con tecnología Flex 4-Way, 3) 6 meses de garantía con la marca, y 4) Servicio exclusivo de tallaje a domicilio en la clínica. Tu objetivo es conectar con directores médicos, jefes de enfermería y encargados de adquisiciones en Chile.";
        }

        $apiKey = Setting::get("{$provider}_api_key");

        if (empty($apiKey)) {
            $fallback = self::smartFallback($prompt);
            return [
                'success' => true,
                'provider' => $provider,
                'model' => 'local-fallback',
                'content' => $fallback,
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
                'is_fallback' => true,
                'note' => 'Generado con el motor local de contingencia. Ingrese su API Key en Ajustes para conectar directamente con ' . strtoupper($provider) . '.'
            ];
        }

        try {
            switch ($provider) {
                case 'groq':
                    $m = $model ?: Setting::get('groq_model', 'llama-3.3-70b-versatile');
                    $content = self::callGroq($apiKey, $m, $prompt, $systemPrompt);
                    break;
                case 'openai':
                    $m = $model ?: Setting::get('openai_model', 'gpt-4o-mini');
                    $content = self::callOpenAI($apiKey, $m, $prompt, $systemPrompt);
                    break;
                case 'claude':
                    $m = $model ?: Setting::get('claude_model', 'claude-3-5-sonnet-20241022');
                    $content = self::callClaude($apiKey, $m, $prompt, $systemPrompt);
                    break;
                case 'gemini':
                    $m = $model ?: Setting::get('gemini_model', 'gemini-1.5-flash');
                    $content = self::callGemini($apiKey, $m, $prompt, $systemPrompt);
                    break;
                default:
                    throw new Exception("Proveedor no soportado: $provider");
            }

            return [
                'success' => true,
                'provider' => $provider,
                'model' => $m ?? $provider,
                'content' => $content,
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
                'is_fallback' => false
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'provider' => $provider,
                'error' => $e->getMessage(),
                'content' => self::smartFallback($prompt),
                'is_fallback' => true,
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    private static function callGroq(string $apiKey, string $model, string $prompt, string $systemPrompt): string
    {
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.65,
            'max_tokens' => 1200
        ];

        return self::postJson('https://api.groq.com/openai/v1/chat/completions', $payload, [
            "Authorization: Bearer $apiKey"
        ], 'choices.0.message.content');
    }

    private static function callOpenAI(string $apiKey, string $model, string $prompt, string $systemPrompt): string
    {
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1200
        ];

        return self::postJson('https://api.openai.com/v1/chat/completions', $payload, [
            "Authorization: Bearer $apiKey"
        ], 'choices.0.message.content');
    }

    private static function callClaude(string $apiKey, string $model, string $prompt, string $systemPrompt): string
    {
        $payload = [
            'model' => $model,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 1200
        ];

        return self::postJson('https://api.anthropic.com/v1/messages', $payload, [
            "x-api-key: $apiKey",
            "anthropic-version: 2023-06-01"
        ], 'content.0.text');
    }

    private static function callGemini(string $apiKey, string $model, string $prompt, string $systemPrompt): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $prompt]]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.6,
                'maxOutputTokens' => 1500
            ]
        ];

        return self::postJson($url, $payload, [], 'candidates.0.content.parts.0.text');
    }

    private static function postJson(string $url, array $payload, array $extraHeaders = [], string $extractKey = ''): string
    {
        $ch = curl_init($url);
        $headers = array_merge(['Content-Type: application/json'], $extraHeaders);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new Exception("Error cURL de conexión: $curlErr");
        }

        $json = json_decode($response, true);
        if ($httpCode >= 400) {
            $msg = $json['error']['message'] ?? $json['message'] ?? "Error HTTP $httpCode del proveedor AI";
            throw new Exception($msg);
        }

        if ($extractKey) {
            $keys = explode('.', $extractKey);
            $val = $json;
            foreach ($keys as $k) {
                if (is_numeric($k)) $k = (int)$k;
                if (!isset($val[$k])) {
                    throw new Exception("No se pudo extraer la clave '$extractKey' de la respuesta AI");
                }
                $val = $val[$k];
            }
            return (string)$val;
        }

        return $response;
    }

    public static function smartFallback(string $prompt): string
    {
        return "Estimado/a Director/a Médico / Encargado/a de Adquisiciones:\n\n" .
               "Junto con saludarle muy cordialmente desde Suitable Uniformes Clínicos (suitable.cl), nos ponemos en contacto con su institución para presentarle nuestra línea de dotaciones médicas confeccionadas en Chile.\n\n" .
               "Nuestros pilares diferenciadores:\n" .
               "• Fabricación chilena directa con 6 meses de garantía integral de fábrica.\n" .
               "• Telas técnicas antifluidos con tecnología Flex 4-Way que garantizan comodidad en turnos de alta exigencia.\n" .
               "• Servicio exclusivo de tallaje en su clínica: Llevamos percheros con muestras (XS a 3XL) sin costo ni compromiso para que su equipo pruebe calces y telas antes de producir.\n\n" .
               "¿Le parece coordinar una breve llamada o visita para acercarle muestrarios físicos a su sede?";
    }
}
