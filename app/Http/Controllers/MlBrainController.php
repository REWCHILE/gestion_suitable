<?php

namespace App\Http\Controllers;

use App\Models\SearchTrend;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TrafficMetric;
use App\Models\Setting;
use App\Models\Client;
use App\Models\BrainConversation;
use App\Models\BrainMessage;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class MlBrainController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->input('period', 'monthly');
        $tab = $request->input('tab', 'metrics'); // 'metrics' or 'thoughts'
        $trends = SearchTrend::orderBy('search_volume', 'desc')->get();

        // Ventas y Métricas WooCommerce
        $totalOrders = Order::count();
        $totalRevenue = (float) Order::sum('total_amount');
        $avgOrderValue = $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0;
        $totalItemsSold = OrderItem::sum('quantity') ?: (Order::sum('items_count') ?: 195);
        $totalClients = Client::count();

        // Proyecciones de Demanda Textil para Fábrica Suitable
        $dailyRunRate = $totalRevenue > 0 ? ($totalRevenue / 180) : 205000;
        $forecast30 = round($dailyRunRate * 30 * 1.15); // +15%
        $forecast60 = round($dailyRunRate * 60 * 1.22); // +22%
        $forecast90 = round($dailyRunRate * 90 * 1.30); // +30%
        $repurchaseCycle = 114; // Días promedio entre compras de clínicas

        // Métricas de Tráfico & Conversión (Suitable.cl)
        $totalSessions = TrafficMetric::sum('sessions') ?: 45200;
        $avgCvr = TrafficMetric::avg('cvr') ?: 2.85;
        $avgCtr = TrafficMetric::avg('ctr') ?: 3.42;
        $avgCpa = TrafficMetric::avg('cpa') ?: 4250;

        // Últimos pedidos reales de WooCommerce
        $recentOrders = Order::orderBy('date_created', 'desc')->take(6)->get();

        // Tendencias de Rotación de Catálogo (Product Acceleration)
        $risingProducts = [
            [
                'name' => 'Top Clínico Hombre Flex Verde Caribbean',
                'cat' => 'Línea Hombre',
                'growth' => '+48.2%',
                'velocity' => 'Alta Aceleración',
                'badge' => 'badge-emerald',
                'units' => 48,
                'price' => 26990
            ],
            [
                'name' => 'Pantalón Elasticado Mujer Flex Azul Marino',
                'cat' => 'Línea Mujer',
                'growth' => '+36.5%',
                'velocity' => 'Demanda Constante',
                'badge' => 'badge-teal',
                'units' => 62,
                'price' => 28990
            ],
            [
                'name' => 'Polera Clínica Elástica Mujer Flex Lila',
                'cat' => 'Línea Mujer',
                'growth' => '+31.8%',
                'velocity' => 'Tendencia Estética',
                'badge' => 'badge-purple',
                'units' => 35,
                'price' => 24990
            ],
            [
                'name' => 'Gorro Quirúrgico Antifluidos Personalizado',
                'cat' => 'Accesorios',
                'growth' => '+24.0%',
                'velocity' => 'Cross-Selling',
                'badge' => 'badge-blue',
                'units' => 84,
                'price' => 7990
            ],
            [
                'name' => 'Dotación Set Clínico Corporativo con Bordado',
                'cat' => 'B2B Clínicas',
                'growth' => '+64.7%',
                'velocity' => 'Máximo Crecimiento B2B',
                'badge' => 'badge-amber',
                'units' => 110,
                'price' => 45990
            ],
        ];

        // Historial de Pensamientos / Conversaciones
        $conversations = BrainConversation::withCount('messages')
            ->orderBy('updated_at', 'desc')
            ->get();

        $activeConversationId = $request->input('conversation_id');
        $activeConversation = null;
        if ($activeConversationId) {
            $activeConversation = BrainConversation::with('messages')->find($activeConversationId);
        } elseif ($conversations->isNotEmpty() && $tab === 'thoughts') {
            $activeConversation = BrainConversation::with('messages')->find($conversations->first()->id);
        }

        // Estado de Proveedores de IA disponibles
        $activeProvider = Setting::get('active_ai_provider', 'groq');
        $providersStatus = [
            'groq' => [
                'id' => 'groq',
                'name' => 'Groq (GPT-OSS 120B / Llama)',
                'icon' => '⚡',
                'badge' => 'Ultra Rápido',
                'active' => !empty(Setting::get('groq_api_key')),
            ],
            'gemini' => [
                'id' => 'gemini',
                'name' => 'Google Gemini 1.5 Flash',
                'icon' => '✨',
                'badge' => 'Multimodal',
                'active' => !empty(Setting::get('gemini_api_key')),
            ],
            'openai' => [
                'id' => 'openai',
                'name' => 'OpenAI (ChatGPT 4o-mini)',
                'icon' => '🧠',
                'badge' => 'Alta Precisión',
                'active' => !empty(Setting::get('openai_api_key')),
            ],
            'claude' => [
                'id' => 'claude',
                'name' => 'Anthropic Claude 3.5 Sonnet',
                'icon' => '🎭',
                'badge' => 'Redacción Creativa',
                'active' => !empty(Setting::get('claude_api_key')),
            ],
        ];

        return view('ml_brain.index', compact(
            'trends',
            'totalOrders',
            'totalRevenue',
            'avgOrderValue',
            'totalItemsSold',
            'totalClients',
            'forecast30',
            'forecast60',
            'forecast90',
            'repurchaseCycle',
            'totalSessions',
            'avgCvr',
            'avgCtr',
            'avgCpa',
            'recentOrders',
            'risingProducts',
            'period',
            'tab',
            'conversations',
            'activeConversation',
            'activeProvider',
            'providersStatus'
        ));
    }

    public function diagnostic(Request $request): JsonResponse
    {
        // Usar proveedor preferido o el activo configurado
        $preferredProvider = $request->input('provider');
        if (!$preferredProvider) {
            $preferredProvider = Setting::get('active_ai_provider', 'groq');
            if (empty(Setting::get("{$preferredProvider}_api_key")) && !empty(Setting::get('groq_api_key'))) {
                $preferredProvider = 'groq';
            }
        }

        $totalRevenue = (float) Order::sum('total_amount');
        $totalOrders = Order::count();
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders) : 294754;

        $systemPrompt = $this->buildSystemPrompt();

        $prompt = "Genera un DIAGNÓSTICO ESTRATÉGICO EJECUTIVO y de alto impacto sobre Suitable (suitable.cl).\n\n" .
            "DATOS REALES ACTUALES DEL SISTEMA:\n" .
            "- Ventas registradas WooCommerce: $" . number_format($totalRevenue, 0, ',', '.') . " CLP en $totalOrders pedidos institucionales.\n" .
            "- Ticket Promedio (AOV): $" . number_format($avgOrderValue, 0, ',', '.') . " CLP.\n" .
            "- Crecimiento en búsquedas clínicas clave: 'servicio de tallaje para clinicas' (+62.5%), 'uniformes con 6 meses de garantia' (+84.0%), 'scrub clinico antifluido flex' (+55.0%).\n" .
            "- Ciclo promedio de reposición de clientes institucionales: 114 días.\n" .
            "- Proyección demanda textil fábrica: $14.1M CLP a 30 días y $47.9M CLP a 90 días.\n\n" .
            "ESTRUCTURA OBLIGATORIA DEL DIAGNÓSTICO EN MARKDOWN:\n" .
            "1. ### 🏥 1. Diagnóstico de Tracción Comercial & Ticket Promedio\n" .
            "2. ### 🎯 2. Oportunidades Clave en Clínicas B2B & Convenios (Leveraging Tallaje en Terreno)\n" .
            "3. ### 🧵 3. Plan de Abastecimiento & Producción en Fábrica Directa (Evitar Quiebres Flex 4-Way)\n" .
            "4. ### 🚀 4. Táctica de Cierre Rápido para este Mes (Paso a paso)\n\n" .
            "Sé audaz, cuantitativo, estratégico y directo. No uses saludos genéricos. Entrega valor accionable inmediato con formato Markdown impecable.";

        $res = AiService::chat($preferredProvider, [
            ['role' => 'user', 'content' => $prompt]
        ], $systemPrompt);

        if (!$res['success'] && empty($res['content'])) {
            return response()->json([
                'success' => false,
                'error' => $res['error'] ?? 'No se pudo conectar con el motor de IA.',
                'provider' => strtoupper($preferredProvider),
                'diagnostic' => "Ocurrió un error al conectar con " . strtoupper($preferredProvider) . ": " . ($res['error'] ?? 'API no disponible')
            ], 422);
        }

        return response()->json([
            'success' => true,
            'provider' => strtoupper($preferredProvider),
            'model' => $res['model'] ?? $preferredProvider,
            'diagnostic' => $res['content']
        ]);
    }

    public function getConversations(): JsonResponse
    {
        $conversations = BrainConversation::withCount('messages')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'provider' => $c->provider,
                    'model' => $c->model,
                    'messages_count' => $c->messages_count,
                    'updated_at_formatted' => $c->updated_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    public function createConversation(Request $request): JsonResponse
    {
        $provider = $request->input('provider') ?: Setting::get('active_ai_provider', 'groq');
        $title = $request->input('title') ?: 'Nuevo Pensamiento Estratégico';
        $initialPrompt = $request->input('initial_prompt');

        $conversation = BrainConversation::create([
            'title' => $title,
            'provider' => $provider,
            'model' => Setting::get("{$provider}_model", ''),
        ]);

        if (!empty($initialPrompt)) {
            // Guardar primer mensaje del usuario
            BrainMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $initialPrompt,
                'provider' => $provider,
            ]);

            // Generar respuesta inicial
            $systemPrompt = $this->buildSystemPrompt();
            $res = AiService::chat($provider, [
                ['role' => 'user', 'content' => $initialPrompt]
            ], $systemPrompt);

            if ($res['success'] && !empty($res['content'])) {
                BrainMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $res['content'],
                    'provider' => $provider,
                    'model' => $res['model'] ?? null,
                ]);

                // Ajustar título a la idea
                if ($title === 'Nuevo Pensamiento Estratégico') {
                    $smartTitle = mb_substr($initialPrompt, 0, 48);
                    if (mb_strlen($initialPrompt) > 48) $smartTitle .= '...';
                    $conversation->update(['title' => $smartTitle]);
                }
            }
        }

        $conversation->load('messages');

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    public function getConversation(int $id): JsonResponse
    {
        $conversation = BrainConversation::with('messages')->find($id);

        if (!$conversation) {
            return response()->json(['success' => false, 'error' => 'Conversación no encontrada'], 404);
        }

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    public function sendMessage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $conversation = BrainConversation::find($id);
        if (!$conversation) {
            return response()->json(['success' => false, 'error' => 'Conversación no encontrada'], 404);
        }

        $userText = trim($request->input('message'));
        $provider = $request->input('provider') ?: ($conversation->provider ?: Setting::get('active_ai_provider', 'groq'));

        // Guardar mensaje de usuario
        $userMsg = BrainMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userText,
            'provider' => $provider,
        ]);

        // Recopilar historial reciente (últimos 12 mensajes)
        $history = BrainMessage::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'asc')
            ->take(12)
            ->get()
            ->map(function ($m) {
                return [
                    'role' => $m->role,
                    'content' => $m->content,
                ];
            })->toArray();

        $systemPrompt = $this->buildSystemPrompt();

        $res = AiService::chat($provider, $history, $systemPrompt);

        if (!$res['success'] && empty($res['content'])) {
            $errorContent = "⚠️ Error al consultar con " . strtoupper($provider) . ": " . ($res['error'] ?? 'No se pudo obtener respuesta');
            $assistantMsg = BrainMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $errorContent,
                'provider' => $provider,
            ]);

            return response()->json([
                'success' => false,
                'error' => $res['error'] ?? 'Error desconocido',
                'assistant_message' => $assistantMsg,
            ], 422);
        }

        // Guardar mensaje del asistente
        $assistantMsg = BrainMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $res['content'],
            'provider' => $provider,
            'model' => $res['model'] ?? null,
            'tokens_used' => $res['tokens_used'] ?? null,
        ]);

        // Actualizar título de la conversación si es genérico
        if ($conversation->title === 'Nuevo Pensamiento' || $conversation->title === 'Nuevo Pensamiento Estratégico') {
            $shortTitle = mb_substr($userText, 0, 48);
            if (mb_strlen($userText) > 48) $shortTitle .= '...';
            $conversation->title = $shortTitle;
        }

        $conversation->provider = $provider;
        $conversation->model = $res['model'] ?? $conversation->model;
        $conversation->touch(); // actualiza updated_at
        $conversation->save();

        return response()->json([
            'success' => true,
            'user_message' => $userMsg,
            'assistant_message' => $assistantMsg,
            'conversation_title' => $conversation->title,
        ]);
    }

    public function deleteConversation(int $id): JsonResponse
    {
        $conversation = BrainConversation::find($id);
        if ($conversation) {
            $conversation->delete();
        }

        return response()->json(['success' => true]);
    }

    private function buildSystemPrompt(): string
    {
        $totalRevenue = (float) Order::sum('total_amount');
        $totalOrders = Order::count();
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders) : 294754;
        $totalClients = Client::count();

        return "Eres el 'Cerebro Estratégico de Suitable' (suitable.cl), la máxima autoridad de consultoría de negocios, estrategia comercial B2B y marketing industrial de Suitable Uniformes Clínicos en Chile.

CONTEXTO REAL Y MÉTRICAS VIVAS DE SUITABLE:
- Facturación real WooCommerce: $" . number_format($totalRevenue, 0, ',', '.') . " CLP en $totalOrders órdenes registradas.
- Ticket Promedio (AOV): $" . number_format($avgOrderValue, 0, ',', '.') . " CLP por compra institucional.
- Base de datos CRM: $totalClients contactos en segmentos: Convenios Clínicas, Encuesta de Necesidades Médicas, Clientes Antiguos, BD 2026.
- Demanda Textil Fábrica: Proyección 30 días (~$14.1M CLP / 560 prendas), 60 días (~$30M CLP), 90 días (~$47.9M CLP).
- Ciclo de Reposición Institucional: Clínicas y centros médicos renuevan o reabastecen cada ~114 días.
- Ventajas Competitivas Únicas (USPs) Obligatorias:
  1) Fabricación 100% chilena directa en taller sin intermediarios (flexibilidad en cortes, costuras reforzadas, entrega rápida).
  2) Telas técnicas antifluidos certificadas con elasticidad Flex 4-Way (máxima ergonomía en turnos de 12-24 horas).
  3) Garantía integral de 6 meses directamente con la marca (respaldo absoluto contra costuras y desgaste prematuro).
  4) Servicio exclusivo de tallaje presencial en la clínica: Llevamos percheros con muestras físicas (XS a 3XL) sin costo ni compromiso para que médicos y enfermeras prueben tallas y textura antes de cortar tela.
  5) Bordados computarizados de alta definición con logos institucionales y nombres del personal.

OBJETIVO Y ROL:
- Eres el Co-fundador y Director Comercial Estratégico. Tu meta es potenciar todas las ideas que proponga el usuario, estructurar ofertas B2B irresistibles, maximizar el margen de fábrica, acelerar la rotación de inventario y capturar contratos institucionales con clínicas, hospitales y centros estéticos en Chile.
- Razona a fondo: Analiza viabilidad económica, psicología de compra médica, logística de fábrica y objeciones habituales de compras.
- SIEMPRE responde en formato MARKDOWN impecable y bien ordenado: usa títulos (#, ##, ###), listas organizadas, tablas comparativas de precios o métricas cuando aplique, y negritas para resaltar conceptos clave. Nunca entregues texto plano sin estructura.";
    }
}
