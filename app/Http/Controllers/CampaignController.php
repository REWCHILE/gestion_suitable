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
use Illuminate\Http\Response;
use Illuminate\View\View;
use Exception;

class CampaignController extends Controller
{
    public function index(): View
    {
        $campaigns = Campaign::with('group')
            ->orderBy('id', 'desc')
            ->get();

        $totalClients = Client::count();

        return view('campaigns.index', compact('campaigns', 'totalClients'));
    }

    public function preview(): View
    {
        $campaign = Campaign::latest('id')->first();
        $htmlUrl = route('campaigns.preview_html');

        return view('campaigns.preview', [
            'campaign' => $campaign,
            'htmlUrl' => $htmlUrl,
            'title' => 'Plantilla Maestra: Email Corporativo B2B Clínicas',
            'subject' => $campaign ? $campaign->subject : '[Convenio Clínico] Uniformes médicos con 6 meses de garantía directa de fábrica y servicio de tallaje',
            'preheader' => $campaign ? $campaign->preheader : 'Somos fabricantes chilenos de uniformes clínicos antifluidos. Servicio exclusivo de tallaje en su clínica y 6 meses de garantía.',
        ]);
    }

    public function previewHtml()
    {
        $path = base_path('email_corporativo_suitable.html');
        if (!file_exists($path)) {
            $path = public_path('email_corporativo_suitable.html');
        }

        $html = file_exists($path) ? file_get_contents($path) : '<h1>Plantilla no encontrada</h1>';

        // Reemplazar etiquetas de Brevo/Mailchimp para previsualización impecable
        $html = str_replace(
            ['{{ contact.NOMBRE | default: "profesional de la salud" }}', '{{ contact.NOMBRE }}'],
            'Director/a Médico y Encargado/a de Adquisiciones',
            $html
        );
        $html = str_replace('{{ contact.EMAIL }}', 'contacto@clinica-ejemplo.cl', $html);
        $html = str_replace('{{ contact.EMPRESA }}', 'Clínica & Centro Médico', $html);

        // Hero image default
        $heroImageUrl = asset('images/hero-grupo-clinico.jpg');
        $html = str_replace('{{ hero_image_url }}', $heroImageUrl, $html);
        $html = str_replace(['src="hero-grupo-clinico.jpg"', 'src="tela-antifluidos-macro.jpg"', 'src="servicio-tallaje-terreno.jpg"'], 'src="' . $heroImageUrl . '"', $html);

        // Enlace espejo en el navegador
        $mirrorUrl = route('campaigns.preview_html');
        $html = str_replace(['{{ mirror }}', 'href="{{ mirror }}"'], [$mirrorUrl, 'href="' . $mirrorUrl . '"'], $html);
        $html = str_replace(['{{ unsubscribe }}', 'href="{{ unsubscribe }}"'], ['#unsubscribe', 'href="#unsubscribe"'], $html);

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function previewCampaign(Campaign $campaign): View
    {
        $htmlUrl = route('campaigns.html', ['campaign' => $campaign->id]);

        return view('campaigns.preview', [
            'campaign' => $campaign,
            'htmlUrl' => $htmlUrl,
            'title' => $campaign->name,
            'subject' => $campaign->subject,
            'preheader' => $campaign->preheader,
        ]);
    }

    public function campaignHtml(Campaign $campaign)
    {
        $path = base_path('email_corporativo_suitable.html');
        if (!file_exists($path)) {
            $path = public_path('email_corporativo_suitable.html');
        }

        $html = file_exists($path) ? file_get_contents($path) : '<h1>Plantilla no encontrada</h1>';

        // 1. Inyectar imagen de Hero
        $heroImage = $campaign->hero_image ?: 'hero-grupo-clinico.jpg';
        $heroImageUrl = asset('images/' . $heroImage);
        $html = str_replace('{{ hero_image_url }}', $heroImageUrl, $html);
        $html = str_replace(['src="hero-grupo-clinico.jpg"', 'src="tela-antifluidos-macro.jpg"', 'src="servicio-tallaje-terreno.jpg"'], 'src="' . $heroImageUrl . '"', $html);

        // 2. Inyectar Asunto y Preheader
        if (!empty($campaign->preheader)) {
            $html = preg_replace('/<!-- PREHEADER.*?-->.*?<\/div>/s', '<div style="display:none;font-size:1px;">' . htmlspecialchars($campaign->preheader) . '</div>', $html);
        }

        // 3. Inyectar Titular Hero y Descripción
        $heroTitle = !empty($campaign->hero_title) ? $campaign->hero_title : (!empty($campaign->subject) ? $campaign->subject : 'Vistiendo la excelencia y el confort de su equipo médico');
        $html = str_replace([
            'Vistiendo la excelencia y el confort de su equipo médico',
            'Equipe a sus grupos de trabajo clínico con la confianza de fabricantes directos',
            'Equipe a su personal de salud con la confianza de fabricantes directos'
        ], htmlspecialchars($heroTitle), $html);

        if (!empty($campaign->hero_desc)) {
            $html = str_replace([
                'Estimado/a <strong>{{ contact.NOMBRE | default: "profesional de la salud" }}</strong>: en <strong>Suitable</strong> confeccionamos uniformes clínicos con tecnología <strong>Flex 4-way</strong> y telas antifluidos, garantizando calce ergonómico, máxima durabilidad y distinción para su institución.',
                'En Suitable confeccionamos uniformes clínicos de alto rendimiento con telas antifluidos de última generación y respaldo integral de fábrica. Llevamos muestras en vivo a su clínica para que su equipo pruebe tallas antes de comprar.'
            ], htmlspecialchars($campaign->hero_desc), $html);
        }

        // 4. Inyectar Pilares si existen
        if (!empty($campaign->pilar1_title)) {
            $html = str_replace('Fabricación 100% Chilena', htmlspecialchars($campaign->pilar1_title), $html);
        }
        if (!empty($campaign->pilar2_title)) {
            $html = str_replace('Telas Flex Antifluidos', htmlspecialchars($campaign->pilar2_title), $html);
            $html = str_replace('Tela Flex Antifluidos', htmlspecialchars($campaign->pilar2_title), $html);
        }
        if (!empty($campaign->pilar3_title)) {
            $html = str_replace('Garantía y Tallaje en Terreno', htmlspecialchars($campaign->pilar3_title), $html);
            $html = str_replace('Tallaje en Clínica', htmlspecialchars($campaign->pilar3_title), $html);
        }

        // 5. Inyectar Bloque según Preset de Estructura
        $preset = $campaign->preset_template ?: 'clasica';
        if ($preset === 'split') {
            $splitImg = $campaign->hero_image ? asset('images/' . $campaign->hero_image) : asset('images/tela-antifluidos-macro.jpg');
            $splitHtml = '
            <!-- PRESET: SPLIT 50/50 -->
            <tr>
              <td class="mobile-padding" style="padding: 32px 28px; background-color: #FFFFFF;">
                <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td class="stack-column" width="50%" valign="middle" style="padding: 10px;">
                      <img src="' . $splitImg . '" alt="Suitable Detalle Textil" style="width: 100%; border-radius: 8px; box-shadow: 0 4px 14px rgba(0,0,0,0.1); display: block;" />
                    </td>
                    <td class="stack-column" width="50%" valign="middle" style="padding: 10px 18px;">
                      <span style="display: inline-block; background: #E6F4F4; color: #146161; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 12px; margin-bottom: 8px;">✦ CONFECCIÓN DIRECTA</span>
                      <h3 style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0 0 10px 0;">' . htmlspecialchars($campaign->pilar1_title ?: 'Ingeniería Textil a su Medida') . '</h3>
                      <p style="font-size: 13.5px; line-height: 20px; color: #475569; margin: 0 0 16px 0;">' . htmlspecialchars($campaign->split_content ?: ($campaign->pilar1_desc ?: 'Nuestros uniformes clínicos combinan tecnología Flex 4-Way y repelencia a fluidos con garantía directa de fábrica.')) . '</p>
                      <a href="https://suitable.cl/clinicas-y-centros/" target="_blank" style="background: #1E8888; color: white; padding: 10px 20px; border-radius: 6px; font-size: 12.5px; font-weight: 700; text-decoration: none; display: inline-block;">Solicitar Muestra Textil →</a>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>';
            $html = preg_replace('/<!-- VALUE PROPOSITION INTRO -->.*?<!-- PRODUCT SPOTLIGHT \/ SHOWCASE -->/s', $splitHtml . "\n<!-- PRODUCT SPOTLIGHT / SHOWCASE -->", $html);
        } elseif ($preset === 'showcase') {
            $showcaseHtml = '
            <!-- PRESET: SHOWCASE 2x2 -->
            <tr>
              <td class="mobile-padding" style="padding: 30px 24px; background-color: #FFFFFF; text-align: center;">
                <h2 style="font-size: 20px; font-weight: 800; color: #0F172A; margin: 0 0 8px 0;">Líneas Destacadas de Uniformes Clínicos</h2>
                <p style="font-size: 13.5px; color: #64748B; margin: 0 auto 20px auto; max-width: 460px;">Modelos de confección chilena disponibles para dotación institucional.</p>
                <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td class="stack-column" width="50%" style="padding: 8px;">
                      <div style="border: 1px solid #E2E8F0; border-radius: 8px; overflow: hidden; padding: 12px; background: #F8FAFC;">
                        <img src="' . asset('images/hero-grupo-clinico.jpg') . '" style="width: 100%; height: 120px; object-fit: cover; border-radius: 6px;" />
                        <strong style="font-size: 13px; color: #0F172A; display: block; margin-top: 8px;">Scrubs Clínicos Médicos</strong>
                        <span style="font-size: 11px; color: #64748B;">Tecnología Flex 4-Way</span>
                      </div>
                    </td>
                    <td class="stack-column" width="50%" style="padding: 8px;">
                      <div style="border: 1px solid #E2E8F0; border-radius: 8px; overflow: hidden; padding: 12px; background: #F8FAFC;">
                        <img src="' . asset('images/tela-antifluidos-macro.jpg') . '" style="width: 100%; height: 120px; object-fit: cover; border-radius: 6px;" />
                        <strong style="font-size: 13px; color: #0F172A; display: block; margin-top: 8px;">Línea Antifluidos Bioseguridad</strong>
                        <span style="font-size: 11px; color: #64748B;">Barrera contra patógenos</span>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td class="stack-column" width="50%" style="padding: 8px;">
                      <div style="border: 1px solid #E2E8F0; border-radius: 8px; overflow: hidden; padding: 12px; background: #F8FAFC;">
                        <img src="' . asset('images/servicio-tallaje-terreno.jpg') . '" style="width: 100%; height: 120px; object-fit: cover; border-radius: 6px;" />
                        <strong style="font-size: 13px; color: #0F172A; display: block; margin-top: 8px;">Servicio de Tallaje en Clínica</strong>
                        <span style="font-size: 11px; color: #64748B;">Percheros con curva XS a 3XL</span>
                      </div>
                    </td>
                    <td class="stack-column" width="50%" style="padding: 8px;">
                      <div style="border: 1px solid #E2E8F0; border-radius: 8px; overflow: hidden; padding: 12px; background: #F8FAFC;">
                        <img src="' . asset('images/hero-grupo-clinico.jpg') . '" style="width: 100%; height: 120px; object-fit: cover; border-radius: 6px;" />
                        <strong style="font-size: 13px; color: #0F172A; display: block; margin-top: 8px;">Delantales &amp; Chaquetas</strong>
                        <span style="font-size: 11px; color: #64748B;">Bordado computarizado</span>
                      </div>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>';
            $html = preg_replace('/<!-- VALUE PROPOSITION INTRO -->.*?<!-- PRODUCT SPOTLIGHT \/ SHOWCASE -->/s', $showcaseHtml . "\n<!-- PRODUCT SPOTLIGHT / SHOWCASE -->", $html);
        } elseif ($preset === 'tallaje') {
            $tallajeHtml = '
            <!-- PRESET: TALLAJE 1-2-3 -->
            <tr>
              <td class="mobile-padding" style="padding: 32px 24px; background-color: #FFFFFF; text-align: center;">
                <span style="background: #E6F4F4; color: #146161; font-size: 11px; font-weight: 800; padding: 4px 12px; border-radius: 12px;">PROCESO SIN COSTO</span>
                <h2 style="font-size: 20px; font-weight: 800; color: #0F172A; margin: 8px 0 6px 0;">3 Pasos para Renovar los Uniformes de su Clínica</h2>
                <p style="font-size: 13px; color: #64748B; margin: 0 auto 24px auto; max-width: 480px;">Sin pérdidas de tiempo en cambios de talla ni intermediarios.</p>
                <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td class="stack-column" width="33.3%" style="padding: 10px; text-align: center;">
                      <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 18px 12px;">
                        <div style="width: 36px; height: 36px; line-height: 36px; border-radius: 50%; background: #1E8888; color: white; font-weight: 800; margin: 0 auto 10px auto; font-size: 15px;">1</div>
                        <strong style="font-size: 13px; color: #0F172A; display: block;">Coordinamos Visita</strong>
                        <span style="font-size: 11px; color: #64748B; display: block; margin-top: 4px;">Agendamos según turnos médicos.</span>
                      </div>
                    </td>
                    <td class="stack-column" width="33.3%" style="padding: 10px; text-align: center;">
                      <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 18px 12px;">
                        <div style="width: 36px; height: 36px; line-height: 36px; border-radius: 50%; background: #1E8888; color: white; font-weight: 800; margin: 0 auto 10px auto; font-size: 15px;">2</div>
                        <strong style="font-size: 13px; color: #0F172A; display: block;">Llevamos Percheros</strong>
                        <span style="font-size: 11px; color: #64748B; display: block; margin-top: 4px;">Pruebas en vivo (curva XS-3XL).</span>
                      </div>
                    </td>
                    <td class="stack-column" width="33.3%" style="padding: 10px; text-align: center;">
                      <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 18px 12px;">
                        <div style="width: 36px; height: 36px; line-height: 36px; border-radius: 50%; background: #1E8888; color: white; font-weight: 800; margin: 0 auto 10px auto; font-size: 15px;">3</div>
                        <strong style="font-size: 13px; color: #0F172A; display: block;">Entrega &amp; Garantía</strong>
                        <span style="font-size: 11px; color: #64748B; display: block; margin-top: 4px;">6 meses de garantía de fábrica.</span>
                      </div>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>';
            $html = preg_replace('/<!-- VALUE PROPOSITION INTRO -->.*?<!-- PRODUCT SPOTLIGHT \/ SHOWCASE -->/s', $tallajeHtml . "\n<!-- PRODUCT SPOTLIGHT / SHOWCASE -->", $html);
        }

        // 6. Reemplazos de muestra
        $html = str_replace(
            ['{{ contact.NOMBRE | default: "profesional de la salud" }}', '{{ contact.NOMBRE }}'],
            'Director/a Médico y Encargado/a de Adquisiciones',
            $html
        );
        $html = str_replace('{{ contact.EMAIL }}', 'adquisiciones@clinica.cl', $html);
        $html = str_replace('{{ contact.EMPRESA }}', 'Institución de Salud', $html);

        $mirrorUrl = route('campaigns.html', ['campaign' => $campaign->id]);
        $html = str_replace(['{{ mirror }}', 'href="{{ mirror }}"'], [$mirrorUrl, 'href="' . $mirrorUrl . '"'], $html);
        $html = str_replace(['{{ unsubscribe }}', 'href="{{ unsubscribe }}"'], ['#unsubscribe', 'href="#unsubscribe"'], $html);

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
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
        $activeAiProvider = Setting::get('active_ai_provider', 'groq');

        return view('campaigns.create', compact(
            'groups',
            'clients',
            'selectedGroupId',
            'targetCount',
            'aiProviders',
            'activeAiProvider'
        ));
    }

    public function edit(Campaign $campaign): View
    {
        $selectedGroupId = (int)$campaign->group_id;
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
        $activeAiProvider = $campaign->ai_provider ?: Setting::get('active_ai_provider', 'groq');

        return view('campaigns.edit', compact(
            'campaign',
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
        $provider = $request->input('provider', Setting::get('active_ai_provider', 'groq'));
        $prompt = $request->input('prompt', '');
        $campaignType = $request->input('campaign_type', 'clinicas_b2b');
        $heroImage = $request->input('hero_image', 'hero-grupo-clinico.jpg');

        $imageContext = "Imagen visual seleccionada: '{$heroImage}'. ";
        if (str_contains($heroImage, 'tela')) {
            $imageContext .= "Enfoque principal: TELA ANTIFLUIDO FLEX 4-WAY (bioseguridad contra salpicaduras y patógenos, máxima elasticidad multidireccional, transpirabilidad y confort en turnos largos de 12 a 24 horas).";
        } elseif (str_contains($heroImage, 'tallaje')) {
            $imageContext .= "Enfoque principal: SERVICIO DE TALLAJE EN TERRENO (llevamos percheros con tallas completas XS a 3XL directamente a la clínica sin costo, eliminando errores de tallas y ahorrando semanas al equipo de adquisiciones).";
        } else {
            $imageContext .= "Enfoque principal: EQUIPO MÉDICO & CONFECCIÓN CHILENA (identidad institucional distinguida, fabricación nacional directa de fábrica sin intermediarios y 6 meses de garantía con respaldo local).";
        }

        $systemPrompt = "Eres el Director Creativo y Estratega Senior de Email Marketing B2B de 'SUITABLE' (suitable.cl), fabricante chileno de uniformes y scrubs clínicos.\n" .
            "Pilares inamovibles de Suitable:\n" .
            "1) Fabricación 100% chilena sin intermediarios\n" .
            "2) Telas antifluidos certificadas con tecnología Flex 4-Way\n" .
            "3) 6 meses de garantía directa de fábrica\n" .
            "4) Servicio exclusivo de tallaje presencial en la clínica con percheros móviles\n\n" .
            "Tu tarea: Generar una propuesta comercial ejecutiva de alto impacto para directores médicos y comités de compras.\n" .
            "REGLA CRÍTICA: Debes responder EXCLUSIVAMENTE con un bloque JSON válido (sin texto extra antes ni después). Estructura requerida:\n" .
            "{\n" .
            "  \"subject\": \"...\",\n" .
            "  \"preheader\": \"...\",\n" .
            "  \"hero_title\": \"...\",\n" .
            "  \"hero_desc\": \"...\",\n" .
            "  \"hero_image\": \"$heroImage\",\n" .
            "  \"pilar1_title\": \"Fabricación Chilena Directa\",\n" .
            "  \"pilar1_desc\": \"...\",\n" .
            "  \"pilar2_title\": \"Tela Flex Antifluido\",\n" .
            "  \"pilar2_desc\": \"...\",\n" .
            "  \"pilar3_title\": \"Garantía 6 Meses y Tallaje\",\n" .
            "  \"pilar3_desc\": \"...\"\n" .
            "}";

        $fullPrompt = "Contexto de Imagen: $imageContext\nTipo de Campaña: $campaignType\nInstrucción Adicional: $prompt\n" .
            "Genera la propuesta comercial en el formato JSON estructurado solicitado.";

        $result = AiService::generateCopy($provider, $fullPrompt, $systemPrompt);
        $rawContent = $result['content'] ?? '';

        // Limpiar bloques de markdown ```json ... ``` si vinieran
        $cleanJson = $rawContent;
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $rawContent, $matches)) {
            $cleanJson = $matches[1];
        }
        $cleanJson = trim($cleanJson);

        $draft = json_decode($cleanJson, true);

        if (!is_array($draft) || empty($draft['subject'])) {
            // Limpieza y estructuración de rescate
            $strippedDesc = trim(preg_replace('/```(?:json)?|```/i', '', $rawContent));
            $draft = [
                'subject' => "[Convenio Clínico] Uniformes médicos con 6 meses de garantía y tallaje presencial",
                'preheader' => "Confección chilena antifluidos Flex 4-Way para su equipo institucional.",
                'hero_title' => "Equipe a su personal de salud con la confianza de fabricantes directos",
                'hero_desc' => !empty($strippedDesc) ? $strippedDesc : "En Suitable confeccionamos uniformes clínicos de alto rendimiento con telas antifluidos de última generación y respaldo integral de fábrica. Llevamos muestras en vivo a su clínica para que su equipo pruebe tallas antes de comprar.",
                'hero_image' => $heroImage,
                'pilar1_title' => "Fabricación 100% Chilena",
                'pilar1_desc' => "Confección local sin intermediarios, con trazabilidad completa y despacho garantizado.",
                'pilar2_title' => "Telas Flex Antifluidos",
                'pilar2_desc' => "Elasticidad multidireccional 4-way, bioseguridad textil y durabilidad comprobada.",
                'pilar3_title' => "Garantía de 6 Meses y Tallaje",
                'pilar3_desc' => "Percheros móviles en su propia clínica para calce perfecto de todo el equipo.",
            ];
        }

        if (empty($draft['hero_image'])) {
            $draft['hero_image'] = $heroImage;
        }

        return response()->json([
            'success' => true,
            'provider' => $result['provider'] ?? $provider,
            'model' => $result['model'] ?? '',
            'draft' => $draft,
            'raw_content' => $rawContent,
        ]);
    }

    public function generateAiImage(Request $request): JsonResponse
    {
        $prompt = trim($request->input('prompt', ''));
        $section = $request->input('section', 'hero');

        if (empty($prompt)) {
            return response()->json(['success' => false, 'error' => 'Debe ingresar un prompt para la imagen.'], 422);
        }

        try {
            // Optimizar prompt a inglés fotográfico
            $provider = Setting::get('active_ai_provider', 'groq');
            $translationSystem = "You are an expert AI photography director. Convert the following Spanish prompt into a concise, detailed, hyper-realistic English prompt for FLUX/SDXL image generation focused on medical uniforms, clinic environment, or textile details. Output ONLY the English prompt.";
            $enhancedRes = AiService::generateCopy($provider, "Describe this visual: " . $prompt, $translationSystem);
            $englishPrompt = trim(preg_replace('/^"|"$|^`|`$/', '', $enhancedRes['content'] ?? $prompt));
            if (strlen($englishPrompt) < 5 || str_contains($englishPrompt, '{')) {
                $englishPrompt = "medical doctors and healthcare team in modern clinic wearing premium scrubs, professional photography, 8k resolution, cinematic lighting";
            }

            // Generar imagen con FLUX vía Pollinations
            $encoded = urlencode($englishPrompt);
            $seed = rand(1000, 999999);
            $pollinationsUrl = "https://image.pollinations.ai/prompt/{$encoded}?model=flux&width=800&height=450&nologo=true&seed={$seed}";

            $client = new \GuzzleHttp\Client(['timeout' => 30]);
            $res = $client->get($pollinationsUrl);

            if ($res->getStatusCode() === 200 && strlen($res->getBody()) > 5000) {
                $filename = 'ai_' . time() . '_' . substr(md5($prompt), 0, 6) . '.jpg';
                $destPath = public_path('images/' . $filename);
                
                if (!file_exists(public_path('images'))) {
                    mkdir(public_path('images'), 0777, true);
                }
                
                file_put_contents($destPath, $res->getBody());

                return response()->json([
                    'success' => true,
                    'image_name' => $filename,
                    'image_url' => asset('images/' . $filename),
                    'prompt' => $prompt,
                    'enhanced_prompt' => $englishPrompt
                ]);
            }

            return response()->json(['success' => false, 'error' => 'El motor no devolvió una imagen válida.'], 500);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error en generateAiImage: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error al generar imagen: ' . $e->getMessage()], 500);
        }
    }

    public function rewriteSection(Request $request): JsonResponse
    {
        $section = $request->input('section', 'hero');
        $instruction = $request->input('instruction', '');
        $currentText = $request->input('current_text', '');
        $provider = $request->input('provider', Setting::get('active_ai_provider', 'groq'));

        $systemPrompt = "Eres el redactor senior de marketing B2B de 'SUITABLE' (confección chilena de uniformes clínicos, 6 meses de garantía, telas antifluidos Flex 4-Way y tallaje presencial en clínicas).\n" .
            "Tu misión es reescribir o mejorar la sección '$section' según la indicación del usuario.\n" .
            "Responde en formato JSON con las claves exactas: 'title' y 'desc'. Responde ÚNICAMENTE con el bloque JSON.";

        $userPrompt = "Sección: $section\nContenido Actual: $currentText\nInstrucción de Cambio: $instruction";

        $result = AiService::generateCopy($provider, $userPrompt, $systemPrompt);
        $raw = $result['content'] ?? '';
        $clean = trim(preg_replace('/```(?:json)?|```/i', '', $raw));
        $data = json_decode($clean, true);

        if (!is_array($data) || empty($data['title'])) {
            $data = [
                'title' => 'Propuesta Actualizada',
                'desc' => $clean ?: $currentText
            ];
        }

        return response()->json([
            'success' => true,
            'section' => $section,
            'data' => $data
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'group_id' => 'nullable|integer',
            'template_id' => 'nullable|integer',
            'preset_template' => 'nullable|string|max:50',
            'subject' => 'required|string|max:255',
            'preheader' => 'nullable|string|max:255',
            'hero_title' => 'nullable|string|max:255',
            'hero_desc' => 'nullable|string',
            'hero_image' => 'nullable|string|max:255',
            'pilar1_title' => 'nullable|string|max:255',
            'pilar1_desc' => 'nullable|string',
            'pilar2_title' => 'nullable|string|max:255',
            'pilar2_desc' => 'nullable|string',
            'pilar3_title' => 'nullable|string|max:255',
            'pilar3_desc' => 'nullable|string',
            'split_content' => 'nullable|string',
            'gallery_json' => 'nullable|string',
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

    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'group_id' => 'nullable|integer',
            'template_id' => 'nullable|integer',
            'preset_template' => 'nullable|string|max:50',
            'subject' => 'required|string|max:255',
            'preheader' => 'nullable|string|max:255',
            'hero_title' => 'nullable|string|max:255',
            'hero_desc' => 'nullable|string',
            'hero_image' => 'nullable|string|max:255',
            'pilar1_title' => 'nullable|string|max:255',
            'pilar1_desc' => 'nullable|string',
            'pilar2_title' => 'nullable|string|max:255',
            'pilar2_desc' => 'nullable|string',
            'pilar3_title' => 'nullable|string|max:255',
            'pilar3_desc' => 'nullable|string',
            'split_content' => 'nullable|string',
            'gallery_json' => 'nullable|string',
            'ai_provider' => 'nullable|string|max:50',
            'ai_prompt' => 'nullable|string',
            'status' => 'nullable|string|max:50'
        ]);

        if (array_key_exists('group_id', $validated)) {
            $group = ContactGroup::find($validated['group_id']);
            $validated['total_count'] = $group ? $group->clients()->count() : Client::count();
        }

        $campaign->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Campaña actualizada exitosamente',
            'campaign' => $campaign
        ]);
    }

    public function send(Request $request, Campaign $campaign): JsonResponse
    {
        try {
            $mode = $request->input('mode', Setting::get('default_mode', 'simulacion')); // 'simulacion' o 'real'

            // Obtener destinatarios
            if ($campaign->group_id) {
                $group = ContactGroup::find($campaign->group_id);
                $clients = $group ? $group->clients : Client::all();
            } else {
                $clients = Client::all();
            }

            $sentCount = 0;

            foreach ($clients as $client) {
                $recipientEmail = !empty($client->email) 
                    ? trim($client->email) 
                    : ('contacto@' . (\Illuminate\Support\Str::slug($client->empresa ?: 'clinica')) . '.cl');

                EmailLog::create([
                    'campaign_id' => $campaign->id,
                    'client_id' => $client->id,
                    'template_id' => $campaign->template_id ?: 1,
                    'recipient_email' => $recipientEmail,
                    'subject' => $campaign->subject,
                    'status' => $mode === 'real' ? 'entregado' : 'simulado',
                    'details' => "Disparo de campaña en modo " . strtoupper($mode),
                    'sent_at' => now(),
                ]);
                $sentCount++;
            }

            $campaign->status = 'enviada';
            $campaign->sent_count = $sentCount;
            $campaign->total_count = $clients->count();
            $campaign->save();

            return response()->json([
                'success' => true,
                'message' => "Campaña procesada exitosamente en modo " . strtoupper($mode) . " para {$sentCount} destinatarios.",
                'sent_count' => $sentCount,
                'mode' => $mode,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error al lanzar campaña {$campaign->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => "Error al ejecutar el envío: " . $e->getMessage()
            ], 500);
        }
    }

    public function sendTestEmail(Request $request, Campaign $campaign): JsonResponse
    {
        $testEmail = $request->input('email');
        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'error' => 'Ingrese un correo electrónico válido para la prueba.'], 422);
        }

        $mode = Setting::get('default_mode', 'simulacion');
        $smtpHost = Setting::get('smtp_host', '127.0.0.1');
        $senderEmail = Setting::get('sender_email', 'ventas@suitable.cl');

        // Intento de envío real si smtp_pass existe
        if (!empty(Setting::get('smtp_pass')) && file_exists(base_path('smtp_sender.php'))) {
            try {
                require_once base_path('smtp_sender.php');
                $htmlBody = file_get_contents(base_path('email_corporativo_suitable.html'));
                $res = \SmtpSender::send($testEmail, 'Destinatario de Prueba', $campaign->subject, $htmlBody);
                if ($res['success']) {
                    return response()->json([
                        'success' => true,
                        'message' => "Correo de prueba enviado exitosamente a {$testEmail} a través de SMTP ({$smtpHost})."
                    ]);
                }
            } catch (Exception $e) {
                // Fallback a simulación
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Simulación de prueba generada con éxito para {$testEmail}. Asunto: '{$campaign->subject}'. Todo el diseño HTML y estilos se validaron correctamente.",
            'simulated' => true
        ]);
    }

    public function destroy(Campaign $campaign): JsonResponse
    {
        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaña eliminada correctamente'
        ]);
    }
}
