<?php
/**
 * AI Service for Suitable B2B Email Orchestrator
 * Connects to Groq, OpenAI (ChatGPT), Anthropic (Claude), and Google (Gemini)
 */

require_once __DIR__ . '/config.php';

class AIService {
    
    public static function getAvailableProviders(): array {
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

    public static function generateCopy(string $provider, string $prompt, string $systemPrompt = '', string $model = ''): array {
        $startTime = microtime(true);
        $provider = strtolower(trim($provider));

        if (!$systemPrompt) {
            $systemPrompt = "Eres el estratega senior de Email Marketing B2B de 'SUITABLE' (suitable.cl), fabricante chileno de uniformes clínicos de alta gama. Tus propuestas de valor obligatorias son: 1) Fabricación 100% chilena sin intermediarios, 2) Telas antifluidos con tecnología Flex 4-Way, 3) 6 meses de garantía con la marca, y 4) Servicio exclusivo de tallaje a domicilio en la clínica. Tu objetivo es conectar con directores médicos, jefes de enfermería y encargados de adquisiciones en Chile.";
        }

        $apiKey = get_setting("{$provider}_api_key");

        // If no API Key configured, use Smart Local Fallback
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
                    $m = $model ?: get_setting('groq_model', 'llama-3.3-70b-versatile');
                    $content = self::callGroq($apiKey, $m, $prompt, $systemPrompt);
                    break;
                case 'openai':
                    $m = $model ?: get_setting('openai_model', 'gpt-4o-mini');
                    $content = self::callOpenAI($apiKey, $m, $prompt, $systemPrompt);
                    break;
                case 'claude':
                    $m = $model ?: get_setting('claude_model', 'claude-3-5-sonnet-20241022');
                    $content = self::callClaude($apiKey, $m, $prompt, $systemPrompt);
                    break;
                case 'gemini':
                    $m = $model ?: get_setting('gemini_model', 'gemini-1.5-flash');
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
            // If live API call fails, return helpful error and fallback
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => $provider,
                'content' => self::smartFallback($prompt),
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
                'is_fallback' => true
            ];
        }
    }

    // Call Groq API
    private static function callGroq(string $apiKey, string $model, string $prompt, string $systemPrompt): string {
        $url = 'https://api.groq.com/openai/v1/chat/completions';
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1024,
        ];
        return self::sendCurlJson($url, $payload, ["Authorization: Bearer $apiKey"]);
    }

    // Call OpenAI API
    private static function callOpenAI(string $apiKey, string $model, string $prompt, string $systemPrompt): string {
        $url = 'https://api.openai.com/v1/chat/completions';
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1024,
        ];
        return self::sendCurlJson($url, $payload, ["Authorization: Bearer $apiKey"]);
    }

    // Call Anthropic Claude API
    private static function callClaude(string $apiKey, string $model, string $prompt, string $systemPrompt): string {
        $url = 'https://api.anthropic.com/v1/messages';
        $payload = [
            'model' => $model,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 1024,
            'temperature' => 0.7,
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "x-api-key: $apiKey",
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_TIMEOUT => 20
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) throw new Exception("Error cURL Claude: $err");
        $json = json_decode($response, true);
        if ($code >= 400) {
            $msg = $json['error']['message'] ?? "HTTP $code";
            throw new Exception("Error Claude ($code): $msg");
        }
        return $json['content'][0]['text'] ?? '';
    }

    // Call Google Gemini API
    private static function callGemini(string $apiKey, string $model, string $prompt, string $systemPrompt): string {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemPrompt . "\n\nSolicitud:\n" . $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1024
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 20
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) throw new Exception("Error cURL Gemini: $err");
        $json = json_decode($response, true);
        if ($code >= 400) {
            $msg = $json['error']['message'] ?? "HTTP $code";
            throw new Exception("Error Gemini ($code): $msg");
        }
        return $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    // Generic JSON Sender for OpenAI/Groq compatible specs
    private static function sendCurlJson(string $url, array $payload, array $headers): string {
        $ch = curl_init($url);
        $headers[] = 'Content-Type: application/json';
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) throw new Exception("Error cURL: $err");
        $json = json_decode($response, true);
        if ($code >= 400) {
            $msg = $json['error']['message'] ?? "HTTP $code";
            throw new Exception("API Error ($code): $msg");
        }
        return $json['choices'][0]['message']['content'] ?? '';
    }

    // Test API Connection
    public static function testConnection(string $provider): array {
        $apiKey = get_setting("{$provider}_api_key");
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'No hay API Key configurada para ' . strtoupper($provider)];
        }
        $res = self::generateCopy($provider, "Responde solo la palabra 'CONECTADO_EXITOSAMENTE'");
        if ($res['success'] && !$res['is_fallback']) {
            return ['success' => true, 'message' => "Conexión exitosa con {$res['provider']} ({$res['model']}) en {$res['latency_ms']}ms"];
        }
        return ['success' => false, 'message' => $res['error'] ?? 'Falló la conexión'];
    }

    // Smart Local Fallback
    private static function smartFallback(string $prompt): string {
        $lower = strtolower($prompt);

        if (strpos($lower, 'asunto') !== false || strpos($lower, 'subject') !== false) {
            return "Aquí tienes 5 opciones de Asuntos B2B de alta tasa de apertura para Suitable:

1. [Convenio Clínico] Dotación de uniformes médicos con 6 meses de garantía directa de fábrica
2. ¿Renovando uniformes clínicos? Llevamos el servicio de tallaje a su clínica sin costo
3. Calidad de confección nacional para su equipo médico: Telas antifluidos y tecnología Flex
4. Propuesta institucional para {{ contact.EMPRESA }}: Uniformes clínicos de alto rendimiento
5. Venta directa de fábrica para clínicas: Conozca el servicio de tallaje en terreno de Suitable";
        }

        if (strpos($lower, 'dental') !== false || strpos($lower, 'odont') !== false) {
            return "Propuesta de Copy B2B para Centros Odontológicos:

Estimado/a {{ contact.NOMBRE }},
Sabemos que en la práctica odontológica la bioseguridad, la estética y la movilidad en sillón son vitales. En Suitable fabricamos uniformes clínicos con tela antifluidos y elasticidad 4-Way que brindan frescura total en jornadas prolongadas.
Además, para evitar errores de pedido, ponemos a disposición de {{ contact.EMPRESA }} nuestro **Servicio de Tallaje en Clínica** y respaldo con **6 Meses de Garantía Oficial**.";
        }

        return "Propuesta B2B Personalizada Suitable:

Estimado/a {{ contact.NOMBRE }}:
En Suitable somos fabricantes chilenos de uniformes clínicos de alta gama. Diseñamos prendas con tecnología textil Flex 4-Way y acabado antifluidos pensadas específicamente para los turnos de {{ contact.EMPRESA }}.

Nuestros diferenciales para su institución:
• Fabricación nacional directa: Precios preferenciales y reposición permanente.
• 6 Meses de Garantía: Calidad respaldada en telas, cierres y costuras.
• Servicio de Tallaje en Terreno: Llevamos el tallero a su clínica para asegurar calce perfecto.
• Bordado corporativo de precisión para todo el equipo.";
    }
}
