<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\ContactGroup;
use App\Models\Client;
use App\Models\EmailLog;
use App\Models\Setting;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Exception;

class CampaignController extends Controller
{
    public function index(): View
    {
        $campaigns = Campaign::with('group')
            ->orderBy('id', 'desc')
            ->get();

        return view('campaigns.index', compact('campaigns'));
    }

    public function create(Request $request): View
    {
        $selectedGroupId = (int)$request->query('group_id', 0);
        $groups = ContactGroup::withCount('clients')->orderBy('name')->get();
        $clients = Client::orderBy('empresa')->get(['id', 'empresa', 'contacto_nombre', 'email', 'cargo']);

        $targetCount = 0;
        if ($selectedGroupId > 0) {
            $g = ContactGroup::find($selectedGroupId);
            $targetCount = $g ? $g->clients()->count() : 0;
        } else {
            $targetCount = Client::count();
        }

        $aiProviders = AiService::getAvailableProviders();
        $activeAiProvider = Setting::get('active_ai_provider', 'gemini');

        return view('campaigns.create', compact(
            'groups',
            'clients',
            'selectedGroupId',
            'targetCount',
            'aiProviders',
            'activeAiProvider'
        ));
    }

    public function generateAi(Request $request): JsonResponse
    {
        $provider = $request->input('provider', 'gemini');
        $prompt = $request->input('prompt', '');
        $campaignType = $request->input('campaign_type', 'clinicas_b2b');

        $systemPrompt = "Eres el estratega senior de Email Marketing B2B de 'SUITABLE' (suitable.cl), fabricante chileno de uniformes clínicos. " .
            "Pilares obligatorios: 1) Fabricación 100% chilena sin intermediarios, 2) Telas antifluidos con tecnología Flex 4-Way, " .
            "3) 6 meses de garantía directa de fábrica, 4) Servicio de tallaje en terreno con percheros en la clínica. " .
            "Genera una propuesta comercial persuasiva y ejecutiva en formato JSON estructurado con: subject, preheader, hero_title, hero_desc, pilar1_title, pilar1_desc, pilar2_title, pilar2_desc, pilar3_title, pilar3_desc.";

        $fullPrompt = "Tipo de Campaña: $campaignType\nInstrucción adicional: $prompt\n" .
            "Genera la propuesta completa para directores médicos y jefes de adquisiciones.";

        $result = AiService::generateCopy($provider, $fullPrompt, $systemPrompt);

        return response()->json($result);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'group_id' => 'nullable|integer',
            'template_id' => 'nullable|integer',
            'subject' => 'required|string|max:255',
            'preheader' => 'nullable|string|max:255',
            'ai_provider' => 'nullable|string|max:50',
            'ai_prompt' => 'nullable|string',
            'status' => 'nullable|string|max:50'
        ]);

        $group = ContactGroup::find($validated['group_id'] ?? null);
        $totalCount = $group ? $group->clients()->count() : Client::count();

        $campaign = Campaign::create(array_merge($validated, [
            'total_count' => $totalCount,
            'status' => $validated['status'] ?? 'borrador'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Campaña guardada exitosamente',
            'campaign' => $campaign
        ]);
    }
}
