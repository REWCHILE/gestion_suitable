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

    /**
     * AI CAMPAIGN ARCHITECT AGENT
     * Interactive Step-by-Step Conversational Engine
     */
    public static function campaignAgentChat(array $history, string $userMessage, array $currentDraft = [], string $provider = ''): array {
        $startTime = microtime(true);
        if (!$provider) {
            $provider = get_setting('active_ai_provider', 'groq');
        }
        $provider = strtolower(trim($provider));

        // Default baseline draft if empty
        $defaultDraft = [
            'campaign_name' => 'Campaña B2B Clínicas - ' . date('d/m/Y'),
            'subject' => '[Convenio Clínico] Uniformes médicos con 6 meses de garantía directa de fábrica y servicio de tallaje',
            'preheader' => 'Somos fabricantes chilenos de uniformes clínicos antifluidos. Servicio exclusivo de tallaje en su clínica y 6 meses de garantía.',
            'hero_title' => 'Equipe a sus grupos de trabajo clínico con la confianza de fabricantes directos',
            'hero_desc' => 'Estimado/a <strong>{{ contact.NOMBRE | default: "Director/a o Encargado/a de Adquisiciones" }}</strong> de <strong>{{ contact.EMPRESA | default: "su institución" }}</strong>: En <strong>Suitable</strong> confeccionamos uniformes clínicos de alto rendimiento con telas antifluidos de última generación y respaldo integral de fábrica.',
            'hero_cta_text' => 'Cotizar Dotación para mi Clínica →',
            'hero_cta_url' => 'https://suitable.cl/clinicas-y-centros/',
            'hero_image' => 'hero-grupo-clinico.jpg',
            'template_id' => 2,
            'pilar1_title' => 'Somos Fabricantes Chilenos con 6 Meses de Garantía',
            'pilar1_desc' => 'Al tratar directamente con la fábrica, su institución accede a mejores costos por volumen, reposición permanente y garantía extendida de 6 meses que cubre confección, costuras y tela.',
            'pilar2_title' => '📏 Servicio de Tallaje a su Equipo Clínico',
            'pilar2_desc' => 'Evite devoluciones y tallas incorrectas. Coordinamos una sesión de tallaje directamente en su clínica con curva de muestras (XS a 3XL) sin costo.',
            'pilar3_title' => 'Telas Antifluidos con Tecnología Flex 4-Way',
            'pilar3_desc' => 'Máxima repelencia a fluidos y salpicaduras con elasticidad multidireccional que asegura confort total en jornadas hospitalarias de alta exigencia.',
        ];

        $draft = array_merge($defaultDraft, array_filter($currentDraft, function($v) { return $v !== null && $v !== ''; }));

        // Detect user acceptance phrases
        $normalizedMsg = mb_strtolower(trim($userMessage), 'UTF-8');
        $isAcceptance = false;
        $acceptancePhrases = ['lo acepto', 'acepto', 'ok', 'okay', 'ya okay', 'ya, okay', 'me gusta', 'perfecto', 'aprobado', 'listo', 'dale', 'me parece bien', 'de acuerdo', 'vamos con este'];
        foreach ($acceptancePhrases as $phrase) {
            if (strpos($normalizedMsg, $phrase) !== false) {
                $isAcceptance = true;
                break;
            }
        }

        $apiKey = get_setting("{$provider}_api_key");

        // Attempt live LLM if API Key exists
        if (!empty($apiKey) && !$isAcceptance) {
            try {
                $systemPrompt = "Eres el 'Arquitecto y Diseñador Senior de Campañas B2B de SUITABLE' (suitable.cl), fabricante chileno de uniformes clínicos y scrubs médicos.
Tu objetivo es guiar al usuario paso a paso y de manera conversacional para crear la campaña de email perfecta, estructurando el contenido y seleccionando las imágenes.
Los 4 pilares irrenunciables de Suitable son:
1) Fabricación chilena 100% directa sin intermediarios.
2) 6 meses de garantía total directa de fábrica.
3) Servicio exclusivo de tallaje a domicilio en la clínica (gratis, con percheros y curva XS a 3XL).
4) Telas antifluidos con tecnología Flex 4-Way y bioseguridad.

Imágenes disponibles en el sistema:
- 'hero-grupo-clinico.jpg': Equipo médico en conjunto luciendo scrubs Suitable (ideal institucional/general).
- 'tela-antifluidos-macro.jpg': Macro con gotas repelentes en tela Flex (ideal para bioseguridad, invierno, odontología).
- 'servicio-tallaje-terreno.jpg': Perchero y prueba de tallas en terreno en clínica (ideal para servicios de tallaje y convenios).

Debes responder SIEMPRE un JSON estrictamente válido con este formato:
{
  \"agent_reply\": \"Mensaje conversacional amigable, persuasivo y claro en español chileno profesional.\",
  \"step\": 1, 2, 3, 4 o 5,
  \"accepted\": false,
  \"suggested_chips\": [\"Chip 1\", \"Chip 2\", \"Chip 3\"],
  \"email_draft\": {
    \"campaign_name\": \"Nombre representativo\",
    \"subject\": \"Asunto de alto open-rate\",
    \"preheader\": \"Preheader complementario\",
    \"hero_title\": \"Título de impacto\",
    \"hero_desc\": \"Párrafo inicial persuasivo\",
    \"hero_cta_text\": \"Texto del botón\",
    \"hero_cta_url\": \"https://suitable.cl/clinicas-y-centros/\",
    \"hero_image\": \"hero-grupo-clinico.jpg\" | \"tela-antifluidos-macro.jpg\" | \"servicio-tallaje-terreno.jpg\",
    \"template_id\": 2,
    \"pilar1_title\": \"...\",
    \"pilar1_desc\": \"...\",
    \"pilar2_title\": \"...\",
    \"pilar2_desc\": \"...\",
    \"pilar3_title\": \"...\",
    \"pilar3_desc\": \"...\"
  }
}";

                $historyContext = "Borrador actual:\n" . json_encode($draft, JSON_UNESCAPED_UNICODE) . "\n\nHistorial de conversación:\n";
                foreach ($history as $h) {
                    $role = $h['role'] ?? 'user';
                    $content = $h['content'] ?? '';
                    $historyContext .= "[$role]: $content\n";
                }
                $historyContext .= "[user]: $userMessage\n\nResponde únicamente el objeto JSON con el agent_reply y el email_draft actualizado:";

                $raw = '';
                if ($provider === 'groq') {
                    $m = get_setting('groq_model', 'llama-3.3-70b-versatile');
                    $raw = self::callGroq($apiKey, $m, $historyContext, $systemPrompt);
                } elseif ($provider === 'openai') {
                    $m = get_setting('openai_model', 'gpt-4o-mini');
                    $raw = self::callOpenAI($apiKey, $m, $historyContext, $systemPrompt);
                } elseif ($provider === 'claude') {
                    $m = get_setting('claude_model', 'claude-3-5-sonnet-20241022');
                    $raw = self::callClaude($apiKey, $m, $historyContext, $systemPrompt);
                } elseif ($provider === 'gemini') {
                    $m = get_setting('gemini_model', 'gemini-1.5-flash');
                    $raw = self::callGemini($apiKey, $m, $historyContext, $systemPrompt);
                }

                // Clean json markdown fences if present
                $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
                $cleaned = preg_replace('/```$/', '', trim($cleaned));
                $parsed = json_decode($cleaned, true);

                if (is_array($parsed) && isset($parsed['agent_reply'])) {
                    if (isset($parsed['email_draft']) && is_array($parsed['email_draft'])) {
                        $draft = array_merge($draft, array_filter($parsed['email_draft']));
                    }
                    return [
                        'success' => true,
                        'agent_reply' => $parsed['agent_reply'],
                        'step' => intval($parsed['step'] ?? 3),
                        'accepted' => !empty($parsed['accepted']),
                        'suggested_chips' => $parsed['suggested_chips'] ?? ['✅ Ya, okay, lo acepto', '✏️ Cambiar Asunto', '🖼️ Cambiar Imagen'],
                        'email_draft' => $draft,
                        'provider' => $provider,
                        'latency_ms' => round((microtime(true) - $startTime) * 1000),
                        'is_fallback' => false
                    ];
                }
            } catch (Exception $e) {
                // Fall through to smart conversational engine
            }
        }

        // SMART CONVERSATIONAL ENGINE (Local Chilean B2B AI Architect)
        $turnCount = count($history);
        $res = self::localConversationalTurn($userMessage, $draft, $turnCount, $isAcceptance);
        $res['latency_ms'] = round((microtime(true) - $startTime) * 1000);
        $res['provider'] = $provider;
        $res['is_fallback'] = empty($apiKey);
        return $res;
    }

    /**
     * Local Conversational Step-by-Step Machine for Campaign Architect
     */
    private static function localConversationalTurn(string $msg, array $draft, int $turnCount, bool $isAcceptance): array {
        $lower = mb_strtolower(trim($msg), 'UTF-8');

        // CASE A: User Approves / Accepts
        if ($isAcceptance) {
            return [
                'success' => true,
                'agent_reply' => "🎉 <strong>¡Excelente decisión! Has aprobado la campaña.</strong><br><br>He guardado la estructura final con el asunto: <em>\"" . htmlspecialchars($draft['subject']) . "\"</em> y la imagen <strong>" . htmlspecialchars($draft['hero_image']) . "</strong>.<br><br>Haz clic en el botón verde abajo o confirma los destinatarios a la derecha para <strong>Lanzar los Envíos B2B</strong>.",
                'step' => 5,
                'accepted' => true,
                'suggested_chips' => [
                    '🚀 Lanzar Campaña Ahora',
                    '🧪 Simulación Segura CRM',
                    '📋 Copiar Código Brevo'
                ],
                'email_draft' => $draft
            ];
        }

        // CASE B: Image Change Instruction
        if (strpos($lower, 'imagen') !== false || strpos($lower, 'foto') !== false || strpos($lower, 'tela') !== false || strpos($lower, 'tallaje') !== false || strpos($lower, 'equipo') !== false) {
            if (strpos($lower, 'tela') !== false || strpos($lower, 'antifluido') !== false || strpos($lower, 'macro') !== false) {
                $draft['hero_image'] = 'tela-antifluidos-macro.jpg';
                $reply = "✅ <strong>He actualizado la imagen principal a la fotografía Macro de Tela Antifluidos Flex.</strong><br><br>Esta imagen destaca de inmediato las gotas repelentes y la bioseguridad ante salpicaduras. Observa cómo cambió en la vista previa a la derecha. ¿Qué te parece o deseas algún otro cambio?";
            } elseif (strpos($lower, 'tallaje') !== false || strpos($lower, 'terreno') !== false || strpos($lower, 'muestrario') !== false) {
                $draft['hero_image'] = 'servicio-tallaje-terreno.jpg';
                $reply = "✅ <strong>He seleccionado la imagen de Servicio de Tallaje en Terreno.</strong><br><br>Muestra nuestros percheros y muestrarios en una clínica real, reforzando la confianza de que el personal se prueba su uniforme antes de confeccionar. ¿Te gustaría ajustar el texto del llamado a la acción?";
            } else {
                $draft['hero_image'] = 'hero-grupo-clinico.jpg';
                $reply = "✅ <strong>He colocado la imagen principal del Equipo Clínico Suitable.</strong><br><br>Transmite un calce impecable y la presencia corporativa de un equipo cohesionado. La previsualización ya está actualizada en vivo.";
            }

            return [
                'success' => true,
                'agent_reply' => $reply,
                'step' => 4,
                'accepted' => false,
                'suggested_chips' => [
                    '✅ Ya, okay, lo acepto',
                    '✏️ Haz el asunto más corto',
                    '🎯 Cambiar botón a WhatsApp',
                    '🛡️ Resaltar garantía de 6 meses'
                ],
                'email_draft' => $draft
            ];
        }

        // CASE C: Subject Line Instruction
        if (strpos($lower, 'asunto') !== false || strpos($lower, 'subject') !== false || strpos($lower, 'título') !== false || strpos($lower, 'corto') !== false) {
            if (strpos($lower, 'corto') !== false || strpos($lower, 'directo') !== false) {
                $draft['subject'] = '[Convenio Clínico] Uniformes de fábrica con 6 meses de garantía y tallaje';
            } elseif (strpos($lower, 'urgente') !== false || strpos($lower, 'invierno') !== false) {
                $draft['subject'] = '❄️ [Temporada Clínica] Renueve dotación con telas antifluidos y tallaje en terreno';
            } elseif (strpos($lower, 'dental') !== false) {
                $draft['subject'] = '🦷 [Especial Odontología] Scrubs Flex 4-Way y tallaje gratuito en su clínica | Suitable';
            } else {
                $draft['subject'] = '[Convenio Directo de Fábrica] Calce perfecto y 6 meses de garantía para su equipo médico';
            }

            return [
                'success' => true,
                'agent_reply' => "✅ <strong>¡Asunto optimizado con éxito!</strong><br><br>El nuevo asunto es:<br>👉 <strong>\"" . htmlspecialchars($draft['subject']) . "\"</strong><br><br>Tiene una longitud ideal para clientes de correo móvil y una clara promesa B2B. ¿Te convence o seguimos afinando?",
                'step' => 4,
                'accepted' => false,
                'suggested_chips' => [
                    '✅ Ya, okay, lo acepto',
                    '🖼️ Cambiar a foto de telas',
                    '💬 Botón para agendar por WhatsApp',
                    '👥 Ajustar audiencia'
                ],
                'email_draft' => $draft
            ];
        }

        // CASE C2: CTA Button / WhatsApp / Contact Link
        if (strpos($lower, 'botón') !== false || strpos($lower, 'boton') !== false || strpos($lower, 'cta') !== false || strpos($lower, 'whatsapp') !== false) {
            if (strpos($lower, 'whatsapp') !== false) {
                $draft['hero_cta_text'] = 'Coordinar con Especialista por WhatsApp →';
                $draft['hero_cta_url'] = 'https://wa.me/56933023278?text=Hola%20Suitable,%20deseamos%20coordinar%20una%20propuesta%20de%20uniformes%20cl%C3%ADnicos%20para%20nuestro%20equipo';
                $reply = "✅ <strong>¡Llamado a la acción modificado a WhatsApp Directo!</strong><br><br>Ahora el botón principal dirige a WhatsApp oficial con mensaje predefinido para una respuesta inmediata. Puedes verlo reflejado en la vista previa.";
            } else {
                $draft['hero_cta_text'] = 'Solicitar Cotización Directa de Fábrica →';
                $reply = "✅ <strong>¡Botón de acción actualizado!</strong><br><br>Se ha configurado para solicitar cotización formal de fábrica sin intermediarios.";
            }

            return [
                'success' => true,
                'agent_reply' => $reply,
                'step' => 4,
                'accepted' => false,
                'suggested_chips' => [
                    '✅ Ya, okay, lo acepto',
                    '🖼️ Cambiar a foto de tallaje',
                    '✏️ Acortar el asunto',
                    '🛡️ Resaltar garantía de 6 meses'
                ],
                'email_draft' => $draft
            ];
        }

        // CASE C3: Highlight Warranty / Direct Factory
        if (strpos($lower, 'garant') !== false || strpos($lower, '6 meses') !== false) {
            $draft['hero_title'] = 'Uniformes clínicos respaldados con 6 Meses de Garantía Oficial de Fábrica';
            $draft['pilar1_title'] = '🛡️ Confección Nacional y 6 Meses de Garantía Total';
            return [
                'success' => true,
                'agent_reply' => "✅ <strong>¡Se ha enfatizado la Garantía Oficial de 6 Meses!</strong><br><br>Hemos puesto en relieve el respaldo de fábrica en el título principal y en el primer bloque del correo para brindar total tranquilidad al comité de compras. ¿Cómo lo ves?",
                'step' => 4,
                'accepted' => false,
                'suggested_chips' => [
                    '✅ Ya, okay, lo acepto',
                    '🖼️ Cambiar imagen',
                    '💬 Botón WhatsApp',
                    '🚀 Lanzar Campaña Ahora'
                ],
                'email_draft' => $draft
            ];
        }

        // CASE D: Step 1 -> Concept Definition
        if ($turnCount <= 1 || strpos($lower, 'invierno') !== false || strpos($lower, 'tallaje') !== false || strpos($lower, 'dental') !== false || strpos($lower, 'reactivaci') !== false || strpos($lower, 'garant') !== false || strpos($lower, 'hospital') !== false) {
            
            // Customize draft based on theme
            if (strpos($lower, 'invierno') !== false || strpos($lower, 'polar') !== false) {
                $draft['campaign_name'] = 'Campaña Invierno & Abrigo Clínico Suitable';
                $draft['subject'] = '❄️ [Temporada Fría] Uniformes clínicos y polar corporativo con 6 meses de garantía de fábrica';
                $draft['preheader'] = 'Proteja y abrigue a su equipo de salud con telas térmicas y antifluidos de confección nacional.';
                $draft['hero_title'] = 'Confort térmico y bioseguridad para su equipo médico este invierno';
                $draft['hero_desc'] = 'Estimado/a <strong>{{ contact.NOMBRE | default: "Director/a o Encargado/a de Adquisiciones" }}</strong> de <strong>{{ contact.EMPRESA | default: "su institución" }}</strong>: Prepare a su dotación médica para las bajas temperaturas con uniformes clínicos de alto rendimiento y chaquetas polar corporativas con bordado oficial de su institución.';
                $draft['hero_image'] = 'tela-antifluidos-macro.jpg';
                $draft['hero_cta_text'] = 'Cotizar Colección Invierno para Clínicas →';
            } elseif (strpos($lower, 'tallaje') !== false || strpos($lower, 'terreno') !== false) {
                $draft['campaign_name'] = 'Campaña Servicio de Tallaje Gratuito en Terreno';
                $draft['subject'] = '📏 [Cero Errores de Talla] Coordinemos una sesión de tallaje en su clínica sin costo';
                $draft['preheader'] = 'Llevamos nuestro muestrario y curva de tallas a su institución para asegurar calce perfecto de todo el equipo.';
                $draft['hero_title'] = 'Elimine devoluciones y problemas de tallas en la dotación de su clínica';
                $draft['hero_desc'] = 'Estimado/a <strong>{{ contact.NOMBRE | default: "Jefe/a de Adquisiciones" }}</strong> de <strong>{{ contact.EMPRESA | default: "su clínica" }}</strong>: En <strong>Suitable</strong> entendemos que cada integrante de su equipo merece un calce impecable. Por eso, ponemos a su disposición nuestro <strong>Servicio Exclusivo de Tallaje en su Institución</strong> sin ningún costo ni compromiso.';
                $draft['hero_image'] = 'servicio-tallaje-terreno.jpg';
                $draft['hero_cta_text'] = 'Coordinar Visita de Tallaje Gratuita →';
            } elseif (strpos($lower, 'dental') !== false || strpos($lower, 'odont') !== false) {
                $draft['campaign_name'] = 'Campaña Centros Odontológicos & Clínicas Dentales';
                $draft['subject'] = '🦷 [Especial Clínicas Dentales] Movilidad total en sillón y repelencia de aerosoles con telas Flex 4-Way';
                $draft['preheader'] = 'Bioseguridad de alto estándar, 6 meses de garantía directa de fábrica y tallaje a domicilio.';
                $draft['hero_title'] = 'Uniformes clínicos de alta ergonomía para profesionales odontológicos';
                $draft['hero_desc'] = 'Estimado/a <strong>{{ contact.NOMBRE | default: "Director/a Dental" }}</strong> de <strong>{{ contact.EMPRESA | default: "su clínica" }}</strong>: La práctica odontológica demanda máxima movilidad en sillón y total repelencia de fluidos y aerosoles. Nuestros scrubs combinan tecnología Flex 4-Way con confección chilena y 6 meses de garantía.';
                $draft['hero_image'] = 'tela-antifluidos-macro.jpg';
                $draft['hero_cta_text'] = 'Cotizar para nuestro Equipo Odontológico →';
            } else {
                $draft['campaign_name'] = 'Campaña B2B Convenio Directo Fábrica ' . date('d/m/Y');
                $draft['subject'] = '[Convenio Institucional] Uniformes clínicos con 6 meses de garantía directa de fábrica y servicio de tallaje';
                $draft['hero_image'] = 'hero-grupo-clinico.jpg';
            }

            return [
                'success' => true,
                'agent_reply' => "💡 <strong>¡Excelente concepto comercial!</strong><br><br>He capturado tu idea y he seleccionado la imagen ideal (<strong>" . htmlspecialchars($draft['hero_image']) . "</strong>) junto con un asunto de alto impacto.<br><br>Para dirigir la propuesta con precisión milimétrica: <strong>¿A qué segmento o audiencia principal nos dirigimos y qué tono de voz prefieres?</strong>",
                'step' => 2,
                'accepted' => false,
                'suggested_chips' => [
                    '🏥 Toda la base de clínicas (General)',
                    '🦷 Clínicas Dentales y Odontológicas',
                    '💉 Jefaturas de Enfermería & Adquisiciones',
                    '👔 Tono Formal Institucional',
                    '🤝 Tono Consultivo Directo de Fábrica'
                ],
                'email_draft' => $draft
            ];
        }

        // CASE E: Step 2 -> Audience / Tone confirmed -> Generate full structure
        return [
            'success' => true,
            'agent_reply' => "🎨 <strong>¡Listo! He estructurado la campaña completa</strong> con los 4 pilares clave de Suitable.<br><br>He configurado:<br>• ✉️ <strong>Asunto:</strong> " . htmlspecialchars($draft['subject']) . "<br>• 📸 <strong>Imagen de Portada:</strong> " . htmlspecialchars($draft['hero_image']) . "<br>• 🎯 <strong>Llamado a la acción:</strong> " . htmlspecialchars($draft['hero_cta_text']) . "<br>• 🛡️ <strong>Garantía:</strong> 6 meses de fábrica sin intermediarios.<br><br>👉 <strong>Revisa la vista previa en vivo a la derecha.</strong><br>¿Qué te parece? <strong>Puedes seguir conversando conmigo para ajustar lo que desees</strong> o si ya te gusta, dime <em>'lo acepto'</em> o presiona el botón verde para continuar.",
            'step' => 3,
            'accepted' => false,
            'suggested_chips' => [
                '✅ Ya, okay, lo acepto',
                '✏️ Haz el asunto más corto',
                '🖼️ Cambiar a foto de tallaje en terreno',
                '🖼️ Cambiar a foto de tela antifluido',
                '💬 Cambiar botón a WhatsApp'
            ],
            'email_draft' => $draft
        ];
    }

    /**
     * RENDER CAMPAIGN HTML FOR PREVIEW & BREVO
     * Substitutes dynamic draft tokens into the responsive email template
     */
    public static function renderCampaignHtml(array $draft): string {
        $templateId = intval($draft['template_id'] ?? 2);
        $fileName = ($templateId === 1) ? 'email_corporativo_suitable_1.html' : 'email_corporativo_suitable_2.html';
        $filePath = __DIR__ . '/' . $fileName;

        if (!file_exists($filePath)) {
            return "<html><body><p>Plantilla base no encontrada</p></body></html>";
        }

        $html = file_get_contents($filePath);

        // Preheader replacement
        if (!empty($draft['preheader'])) {
            $html = preg_replace(
                '/<!-- PREHEADER -->(.*?)(<table)/s',
                "<!-- PREHEADER -->\n  <div style=\"display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all; font-family: sans-serif;\">\n    " . htmlspecialchars($draft['preheader']) . "\n    &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;\n  </div>\n  $2",
                $html
            );
        }

        // Hero Image replacement
        if (!empty($draft['hero_image'])) {
            $imgName = basename($draft['hero_image']);
            $html = preg_replace(
                '/(<td[^>]*line-height:\s*0[^>]*>.*?<img[^>]*src=")[^"]+(")/s',
                "$1$imgName$2",
                $html,
                1
            );
        }

        // Hero Title replacement
        if (!empty($draft['hero_title'])) {
            $html = preg_replace(
                '/(<h1[^>]*class="hero-title"[^>]*>)(.*?)(<\/h1>)/s',
                "$1" . htmlspecialchars($draft['hero_title']) . "$3",
                $html
            );
        }

        // Hero Desc replacement
        if (!empty($draft['hero_desc'])) {
            $html = preg_replace(
                '/(<h1[^>]*class="hero-title"[^>]*>.*?<\/h1>\s*<p[^>]*>)(.*?)(<\/p>)/s',
                "$1" . $draft['hero_desc'] . "$3",
                $html
            );
        }

        // Hero CTA button text & url
        if (!empty($draft['hero_cta_text'])) {
            $ctaUrl = !empty($draft['hero_cta_url']) ? htmlspecialchars($draft['hero_cta_url']) : 'https://suitable.cl/clinicas-y-centros/';
            $html = preg_replace(
                '/(<a[^>]*class="btn-primary"[^>]*href=")[^"]*("[^>]*>)(.*?)(<\/a>)/s',
                "$1$ctaUrl$2" . htmlspecialchars($draft['hero_cta_text']) . "$4",
                $html
            );
        }

        // Pilar 1 title & desc
        if (!empty($draft['pilar1_title'])) {
            $html = preg_replace(
                '/(Sin intermediarios.*?<h3[^>]*>)(.*?)(<\/h3>)/s',
                "$1" . htmlspecialchars($draft['pilar1_title']) . "$3",
                $html
            );
        }
        if (!empty($draft['pilar1_desc'])) {
            $html = preg_replace(
                '/(Sin intermediarios.*?<\/h3>\s*<p[^>]*>)(.*?)(<\/p>)/s',
                "$1" . htmlspecialchars($draft['pilar1_desc']) . "$3",
                $html
            );
        }

        // Pilar 2 title & desc
        if (!empty($draft['pilar2_title'])) {
            $html = preg_replace(
                '/(Cero margen de error.*?<h3[^>]*>)(.*?)(<\/h3>)/s',
                "$1" . htmlspecialchars($draft['pilar2_title']) . "$3",
                $html
            );
        }
        if (!empty($draft['pilar2_desc'])) {
            $html = preg_replace(
                '/(Cero margen de error.*?<\/h3>\s*<p[^>]*>)(.*?)(<\/p>)/s',
                "$1" . htmlspecialchars($draft['pilar2_desc']) . "$3",
                $html
            );
        }

        // Pilar 3 title & desc
        if (!empty($draft['pilar3_title'])) {
            $html = preg_replace(
                '/(Bioseguridad &amp; Confort.*?<h3[^>]*>)(.*?)(<\/h3>)/s',
                "$1" . htmlspecialchars($draft['pilar3_title']) . "$3",
                $html
            );
        }
        if (!empty($draft['pilar3_desc'])) {
            $html = preg_replace(
                '/(Bioseguridad &amp; Confort.*?<\/h3>\s*<p[^>]*>)(.*?)(<\/p>)/s',
                "$1" . htmlspecialchars($draft['pilar3_desc']) . "$3",
                $html
            );
        }

        // Inject base tag in head so images and links resolve cleanly in iframe srcdoc
        if (strpos($html, '<base') === false) {
            $html = str_replace('<head>', "<head>\n  <base href=\"./\">", $html);
        }

        return $html;
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
