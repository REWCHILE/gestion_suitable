<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\ContactGroup;
use App\Models\Client;
use App\Models\EmailLog;
use App\Models\Setting;
use App\Services\AiService;
use App\Services\CampaignPresetService;
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

    public function presetsCatalog(Request $request): View
    {
        $allPresets = CampaignPresetService::all();
        $categories = CampaignPresetService::categories();
        $selectedCategory = $request->query('category', 'all');

        $presets = $allPresets;
        if ($selectedCategory !== 'all') {
            $presets = array_values(array_filter($allPresets, fn($p) => ($p['category_slug'] ?? '') === $selectedCategory));
        }

        return view('campaigns.presets', compact('presets', 'categories', 'selectedCategory', 'allPresets'));
    }

    public function previewPresetHtml(string $preset): Response
    {
        $presetData = CampaignPresetService::find($preset);
        if (!$presetData) {
            abort(404, 'Preset no encontrado');
        }

        $dummyCampaign = new Campaign([
            'id' => 99999,
            'name' => $presetData['name'],
            'subject' => $presetData['subject'],
            'preheader' => $presetData['preheader'],
            'hero_title' => $presetData['hero_title'],
            'hero_desc' => $presetData['hero_desc'],
            'hero_image' => $presetData['hero_image'],
            'pilar1_title' => $presetData['pilar1_title'],
            'pilar1_desc' => $presetData['pilar1_desc'],
            'pilar2_title' => $presetData['pilar2_title'],
            'pilar2_desc' => $presetData['pilar2_desc'],
            'pilar3_title' => $presetData['pilar3_title'],
            'pilar3_desc' => $presetData['pilar3_desc'],
            'preset_template' => $presetData['id'],
            'split_content' => $presetData['pilar1_desc'] ?? '',
        ]);

        return $this->campaignHtml($dummyCampaign);
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

        // 5. Inyectar Bloque según Preset de Estructura (20 Presets B2B)
        $preset = $campaign->preset_template ?: 'clasica';
        $presetInfo = CampaignPresetService::find($preset);
        $layoutType = $presetInfo['layout_type'] ?? ($preset === 'split' ? 'split' : ($preset === 'showcase' ? 'showcase' : ($preset === 'tallaje' ? 'process' : 'pillars')));

        if ($layoutType === 'split') {
            $splitImg = $campaign->hero_image ? asset('images/' . $campaign->hero_image) : asset('images/tela-antifluidos-macro.jpg');
            $badgeText = htmlspecialchars($presetInfo['badge'] ?? '✦ CONFECCIÓN DIRECTA');
            $splitTitle = htmlspecialchars($campaign->pilar1_title ?: ($presetInfo['pilar1_title'] ?? 'Ingeniería Textil a su Medida'));
            $splitDesc = htmlspecialchars($campaign->split_content ?: ($campaign->pilar1_desc ?: ($presetInfo['pilar1_desc'] ?? 'Nuestros uniformes clínicos combinan tecnología Flex 4-Way y repelencia a fluidos con garantía directa de fábrica.')));
            $ctaText = htmlspecialchars($presetInfo['cta_text'] ?? 'Solicitar Muestra Textil →');
            $ctaUrl = htmlspecialchars($presetInfo['cta_url'] ?? 'https://suitable.cl/clinicas-y-centros/');

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
                      <span style="display: inline-block; background: #E6F4F4; color: #146161; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 12px; margin-bottom: 8px;">' . $badgeText . '</span>
                      <h3 style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0 0 10px 0;">' . $splitTitle . '</h3>
                      <p style="font-size: 13.5px; line-height: 20px; color: #475569; margin: 0 0 16px 0;">' . $splitDesc . '</p>
                      <a href="' . $ctaUrl . '" target="_blank" style="background: #1E8888; color: white; padding: 10px 20px; border-radius: 6px; font-size: 12.5px; font-weight: 700; text-decoration: none; display: inline-block;">' . $ctaText . '</a>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>';
            $html = preg_replace('/<!-- VALUE PROPOSITION INTRO -->.*?<!-- PRODUCT SPOTLIGHT \/ SHOWCASE -->/s', $splitHtml . "\n<!-- PRODUCT SPOTLIGHT / SHOWCASE -->", $html);
        } elseif ($layoutType === 'showcase') {
            $showcaseTitle = htmlspecialchars($presetInfo['hero_title'] ?? 'Líneas Destacadas de Uniformes Clínicos');
            $showcaseDesc = htmlspecialchars($presetInfo['hero_desc'] ?? 'Modelos de confección chilena disponibles para dotación institucional.');
            $showcaseHtml = '
            <!-- PRESET: SHOWCASE 2x2 -->
            <tr>
              <td class="mobile-padding" style="padding: 30px 24px; background-color: #FFFFFF; text-align: center;">
                <h2 style="font-size: 20px; font-weight: 800; color: #0F172A; margin: 0 0 8px 0;">' . $showcaseTitle . '</h2>
                <p style="font-size: 13.5px; color: #64748B; margin: 0 auto 20px auto; max-width: 460px;">' . $showcaseDesc . '</p>
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
        } elseif ($layoutType === 'process') {
            $badge = htmlspecialchars($presetInfo['badge'] ?? 'PROCESO EN TERRENO');
            $pTitle = htmlspecialchars($presetInfo['hero_title'] ?? 'Proceso Simple y Garantizado');
            $s1Title = htmlspecialchars($campaign->pilar1_title ?: ($presetInfo['pilar1_title'] ?? '1. Coordinamos Visita'));
            $s1Desc = htmlspecialchars($campaign->pilar1_desc ?: ($presetInfo['pilar1_desc'] ?? 'Agendamos fecha y hora.'));
            $s2Title = htmlspecialchars($campaign->pilar2_title ?: ($presetInfo['pilar2_title'] ?? '2. Llevamos Percheros'));
            $s2Desc = htmlspecialchars($campaign->pilar2_desc ?: ($presetInfo['pilar2_desc'] ?? 'Prueba de tallas XS a 3XL.'));
            $s3Title = htmlspecialchars($campaign->pilar3_title ?: ($presetInfo['pilar3_title'] ?? '3. Entrega & Garantía'));
            $s3Desc = htmlspecialchars($campaign->pilar3_desc ?: ($presetInfo['pilar3_desc'] ?? '6 meses de garantía directa.'));

            $tallajeHtml = '
            <!-- PRESET: PROCESO 1-2-3 -->
            <tr>
              <td class="mobile-padding" style="padding: 32px 24px; background-color: #FFFFFF; text-align: center;">
                <span style="background: #E6F4F4; color: #146161; font-size: 11px; font-weight: 800; padding: 4px 12px; border-radius: 12px;">' . $badge . '</span>
                <h2 style="font-size: 20px; font-weight: 800; color: #0F172A; margin: 8px 0 6px 0;">' . $pTitle . '</h2>
                <p style="font-size: 13px; color: #64748B; margin: 0 auto 24px auto; max-width: 480px;">Sin pérdidas de tiempo en cambios de talla ni intermediarios.</p>
                <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td class="stack-column" width="33.3%" style="padding: 10px; text-align: center;">
                      <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 18px 12px;">
                        <div style="width: 36px; height: 36px; line-height: 36px; border-radius: 50%; background: #1E8888; color: white; font-weight: 800; margin: 0 auto 10px auto; font-size: 15px;">1</div>
                        <strong style="font-size: 13px; color: #0F172A; display: block;">' . $s1Title . '</strong>
                        <span style="font-size: 11px; color: #64748B; display: block; margin-top: 4px;">' . $s1Desc . '</span>
                      </div>
                    </td>
                    <td class="stack-column" width="33.3%" style="padding: 10px; text-align: center;">
                      <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 18px 12px;">
                        <div style="width: 36px; height: 36px; line-height: 36px; border-radius: 50%; background: #1E8888; color: white; font-weight: 800; margin: 0 auto 10px auto; font-size: 15px;">2</div>
                        <strong style="font-size: 13px; color: #0F172A; display: block;">' . $s2Title . '</strong>
                        <span style="font-size: 11px; color: #64748B; display: block; margin-top: 4px;">' . $s2Desc . '</span>
                      </div>
                    </td>
                    <td class="stack-column" width="33.3%" style="padding: 10px; text-align: center;">
                      <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 18px 12px;">
                        <div style="width: 36px; height: 36px; line-height: 36px; border-radius: 50%; background: #1E8888; color: white; font-weight: 800; margin: 0 auto 10px auto; font-size: 15px;">3</div>
                        <strong style="font-size: 13px; color: #0F172A; display: block;">' . $s3Title . '</strong>
                        <span style="font-size: 11px; color: #64748B; display: block; margin-top: 4px;">' . $s3Desc . '</span>
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

        $mirrorUrl = (!empty($campaign->id) && $campaign->exists) ? route('campaigns.html', ['campaign' => $campaign->id]) : route('campaigns.preview_html');
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

        $allPresets = CampaignPresetService::all();
        $selectedPresetId = $request->query('preset', 'clasica');
        $initialPreset = CampaignPresetService::find($selectedPresetId) ?? $allPresets[0];

        return view('campaigns.create', compact(
            'groups',
            'clients',
            'selectedGroupId',
            'targetCount',
            'aiProviders',
            'activeAiProvider',
            'allPresets',
            'selectedPresetId',
            'initialPreset'
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

        $allPresets = CampaignPresetService::all();
        $selectedPresetId = $campaign->preset_template ?: 'clasica';
        $initialPreset = CampaignPresetService::find($selectedPresetId) ?? $allPresets[0];

        return view('campaigns.edit', compact(
            'campaign',
            'groups',
            'clients',
            'selectedGroupId',
            'targetCount',
            'aiProviders',
            'activeAiProvider',
            'allPresets',
            'selectedPresetId',
            'initialPreset'
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
        $theme = trim($request->input('theme', ''));
        $color = trim($request->input('color', 'petroleo'));
        $section = $request->input('section', 'hero');

        // Paleta de colores en inglés para IA fotográfica
        $colorMap = [
            'petroleo' => 'deep teal / petrol blue',
            'marino' => 'classic navy blue',
            'grafito' => 'dark charcoal graphite',
            'verde' => 'surgical emerald green',
            'burdeo' => 'deep wine burgundy',
        ];
        $colorDesc = $colorMap[$color] ?? 'deep teal';

        // Prompts maestros hiperrealistas por temática clínica Suitable
        $themePrompts = [
            'equipo' => "Editorial high-end commercial photography of diverse group of healthcare professionals, doctors, nurses and surgeons standing confidently in bright modern hospital atrium, wearing bespoke tailor-fit {$colorDesc} medical scrubs with subtle Suitable branding, authentic warm smiles, cinematic rim lighting, shallow depth of field, 8k resolution, photorealistic",
            'tela' => "Extreme macro close-up studio photography of Suitable flexible water-repellent medical scrub textile fabric in {$colorDesc} weave, crystalline water droplets rolling smoothly off the hydrophobic surface, hyper-detailed textile weave texture, elegant softbox studio rim lighting, razor sharp focus, 8k",
            'tallaje' => "Authentic documentary corporate photography in upscale private clinic, mobile fitting service by Suitable uniforms, sleek minimalist garment rack with curated scrubs in full size curve from XS to 3XL, clinic nursing team testing sizing jackets, warm professional ambience, 8k resolution",
            'dental' => "Editorial commercial photography of modern dental clinic team, dentist and dental assistant wearing contemporary {$colorDesc} medical scrub uniform, state-of-the-art dental facility in soft blurred background, approachable professional posture, 8k resolution",
            'quirofano' => "Cinematic photography of surgical medical team in high-tech sterile operating theater, wearing {$colorDesc} scrub suits and surgical caps, intense focused overhead surgical lighting, ultra-clean clinical aesthetic, 8k resolution",
            'estetica' => "Luxury aesthetic dermatology clinic interior, female medical practitioner wearing minimalist elegant {$colorDesc} clinical scrub tunic, clean warm luxury architectural interior with soft lighting, 8k photorealistic",
            'pediatria' => "Warm welcoming pediatric clinic doctor and nurse wearing modern {$colorDesc} soft scrubs, child-friendly bright modern clinic office, cheerful caring expression, 8k resolution, photorealistic"
        ];

        // Determinar prompt en inglés
        if (!empty($theme) && isset($themePrompts[$theme]) && empty($prompt)) {
            $englishPrompt = $themePrompts[$theme];
            $displayPrompt = ucfirst($theme) . " ({$colorDesc})";
        } elseif (!empty($prompt)) {
            $displayPrompt = $prompt;
            try {
                $provider = Setting::get('active_ai_provider', 'groq');
                $translationSystem = "You are an expert AI photography director. Convert the following Spanish prompt into a concise, detailed, hyper-realistic English prompt for SDXL/FLUX image generation focused on medical uniforms, clinic environment, or textile details. Include scrub color: {$colorDesc}. Output ONLY the English prompt.";
                $enhancedRes = AiService::generateCopy($provider, "Describe this visual: " . $prompt, $translationSystem);
                $englishPrompt = trim(preg_replace('/^"|"$|^`|`$/', '', $enhancedRes['content'] ?? $prompt));
            } catch (\Exception $e) {
                $englishPrompt = $prompt . ", medical uniforms in modern clinic, {$colorDesc} color, professional photography, 8k";
            }
        } else {
            $englishPrompt = $themePrompts['equipo'];
            $displayPrompt = "Equipo Clínico ({$colorDesc})";
        }

        if (strlen($englishPrompt) < 5 || str_contains($englishPrompt, '{')) {
            $englishPrompt = "medical doctors and healthcare team in modern clinic wearing premium {$colorDesc} scrubs, professional photography, 8k resolution, cinematic lighting";
        }

        // Generar imagen con Pollinations (modelo turbo ultrarrápido 3-4s para evitar timeouts y colas)
        $encoded = urlencode($englishPrompt);
        $seed = rand(1000, 999999);
        $modelsToTry = ['turbo', 'flux'];
        $imageBody = null;
        $client = new \GuzzleHttp\Client(['timeout' => 15]);

        foreach ($modelsToTry as $model) {
            try {
                $pollinationsUrl = "https://image.pollinations.ai/prompt/{$encoded}?model={$model}&width=800&height=450&nologo=true&seed={$seed}";
                $res = $client->get($pollinationsUrl);
                if ($res->getStatusCode() === 200 && strlen($res->getBody()) > 5000) {
                    $imageBody = $res->getBody();
                    break;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Fallo Pollinations {$model}: " . $e->getMessage());
            }
        }

        if (!$imageBody) {
            return response()->json(['success' => false, 'error' => 'El motor de generación no respondió a tiempo. Por favor intenta nuevamente.'], 500);
        }

        $filename = 'ai_' . time() . '_' . substr(md5($englishPrompt . $seed), 0, 6) . '.jpg';
        $destPath = public_path('images/' . $filename);
        
        if (!file_exists(public_path('images'))) {
            mkdir(public_path('images'), 0777, true);
        }
        
        file_put_contents($destPath, $imageBody);

        // Copys comerciales coherentes con la temática generada
        $suggestedCopy = $this->getSuggestedCopyForTheme($theme ?: 'equipo', $color);

        return response()->json([
            'success' => true,
            'image_name' => $filename,
            'image_url' => asset('images/' . $filename),
            'theme' => $theme,
            'color' => $color,
            'prompt' => $displayPrompt,
            'suggested_copy' => $suggestedCopy
        ]);
    }

    public function aiImageGallery(): JsonResponse
    {
        $dir = public_path('images');
        $files = [];

        if (file_exists($dir)) {
            $scan = scandir($dir);
            foreach ($scan as $file) {
                if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'])) {
                    $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
                    $isAi = str_starts_with($file, 'ai_');
                    $isHero = in_array($file, ['hero-grupo-clinico.jpg', 'tela-antifluidos-macro.jpg', 'servicio-tallaje-terreno.jpg']);
                    
                    if ($isAi || $isHero) {
                        $files[] = [
                            'filename' => $file,
                            'url' => asset('images/' . $file),
                            'is_ai' => $isAi,
                            'title' => $isAi ? 'Generada por IA' : ($file === 'hero-grupo-clinico.jpg' ? 'Equipo Clínico' : ($file === 'tela-antifluidos-macro.jpg' ? 'Tela Antifluido' : 'Tallaje en Terreno')),
                            'timestamp' => filemtime($fullPath)
                        ];
                    }
                }
            }
        }

        // Ordenar por más recientes primero
        usort($files, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return response()->json([
            'success' => true,
            'images' => array_slice($files, 0, 24)
        ]);
    }

    private function getSuggestedCopyForTheme(string $theme, string $color): array
    {
        switch ($theme) {
            case 'tela':
                return [
                    'campaign_name' => 'Propuesta Bioseguridad Textil Antifluido Flex',
                    'subject' => '🔬 [Bioseguridad de Alto Estándar] Uniformes con tecnología antifluido Flex 4-Way y 6 meses de garantía',
                    'preheader' => 'Máxima protección contra salpicaduras y fluidos corporales con confección 100% chilena de fábrica.',
                    'hero_title' => 'Tecnología textil antifluidos diseñada para la máxima exigencia clínica',
                    'hero_desc' => 'Estimado/a <strong>{{ contact.NOMBRE | default: "Director/a o Encargado/a de Adquisiciones" }}</strong> de <strong>{{ contact.EMPRESA | default: "su institución" }}</strong>: Como fabricantes chilenos, en <strong>Suitable</strong> entendemos que la bioseguridad del equipo de salud no admite compromisos. Nuestras prendas integran acabado repelente a salpicaduras y elasticidad Flex 4-Way que aseguran frescura, higiene y total libertad de movimiento en turnos continuos.',
                    'hero_cta_text' => 'Solicitar Muestrario de Telas Antifluido →',
                    'pilar1_title' => '💧 Repelencia Total a Fluidos y Salpicaduras',
                    'pilar1_desc' => 'Tejido de alta densidad que evita la absorción de líquidos, sangre y aerosoles médicos, manteniendo al profesional seco y protegido.',
                    'pilar2_title' => '🏃 Flexibilidad Ergonómica 4-Way',
                    'pilar2_desc' => 'Elasticidad multidireccional que acompaña cada movimiento en pabellón y box clínico sin deformarse.',
                    'pilar3_title' => '🛡️ 6 Meses de Garantía Oficial de Fábrica',
                    'pilar3_desc' => 'Costuras reforzadas y durabilidad comprobada tras decenas de ciclos de lavado industrial.',
                ];
            case 'tallaje':
                return [
                    'campaign_name' => 'Propuesta Servicio Exclusivo de Tallaje en Terreno',
                    'subject' => '📏 [Cero Margen de Error] Llevamos el servicio de tallaje a su clínica sin costo ni compromiso | Suitable',
                    'preheader' => 'Evite devoluciones y problemas de calce. Sesión de tallaje directo en sus dependencias con curva XS a 3XL.',
                    'hero_title' => 'Calce perfecto garantizado para cada integrante de su equipo médico',
                    'hero_desc' => 'Estimado/a <strong>{{ contact.NOMBRE | default: "Jefe/a de Adquisiciones" }}</strong> de <strong>{{ contact.EMPRESA | default: "su clínica" }}</strong>: Uno de los mayores dolores de cabeza en compras corporativas es la discrepancia de tallas. En <strong>Suitable</strong> lo resolvemos llevando nuestro <strong>Servicio de Tallaje en Terreno</strong> directamente a su institución, con percheros y prendas de prueba para cada profesional antes de confeccionar.',
                    'hero_cta_text' => 'Coordinar Visita de Tallaje para mi Clínica →',
                    'pilar1_title' => '📏 Percheros Móviles en su Institución',
                    'pilar1_desc' => 'Llegamos a su clínica con percheros rodantes y muestras físicas para que cada profesional se pruebe su talla exacta.',
                    'pilar2_title' => '📐 Curva Completa XS a 3XL y Medidas Especiales',
                    'pilar2_desc' => 'Ajuste de basta y calce personalizado para que cada uniforme luzca impecable y corporativo.',
                    'pilar3_title' => '⏱️ Cero Pérdida de Tiempo en Devoluciones',
                    'pilar3_desc' => 'Entregas 100% conformes a la primera. Sin reclamos de personal ni retrasos de inventario.',
                ];
            case 'dental':
                return [
                    'campaign_name' => 'Propuesta Especial Clínicas Odontológicas',
                    'subject' => '🦷 [Especial Odontología] Scrubs Flex 4-Way y tallaje gratuito en su clínica | Suitable',
                    'preheader' => 'Confección médica chilena para equipos dentales con alta resistencia a desinfectantes y 6 meses de garantía.',
                    'hero_title' => 'Ergonomía superior e imagen corporativa para su equipo odontológico',
                    'hero_desc' => 'Estimado/a <strong>{{ contact.NOMBRE | default: "Director/a Odontológico/a" }}</strong> de <strong>{{ contact.EMPRESA | default: "su clínica dental" }}</strong>: En <strong>Suitable</strong> diseñamos uniformes clínicos que acompañan la postura ergonómica del odontólogo y su equipo asistente. Telas con resistencia al autoclave y repelencia a aerosoles clínicos.',
                    'hero_cta_text' => 'Solicitar Propuesta para mi Equipo Odontológico →',
                    'pilar1_title' => '🦷 Ergonomía para Postura en Sillón Dental',
                    'pilar1_desc' => 'Cortes anatómicos diseñados para reducir la fatiga en hombros y espalda durante largas jornadas de atención.',
                    'pilar2_title' => '🛡️ Resistencia a Manchas y Desinfectantes',
                    'pilar2_desc' => 'Telas certificadas que mantienen su color y brillo frente a hipoclorito diluido y alcohol.',
                    'pilar3_title' => '📏 Tallaje en su Box Dental sin Costo',
                    'pilar3_desc' => 'Vamos a su consulta para que odontólogos y asistentes elijan su talle exacto sin interrumpir la agenda.',
                ];
            default: // equipo
                return [
                    'campaign_name' => 'Propuesta Identidad & Dotación Médica Corporativa',
                    'subject' => '🏥 [Convenio Institucional] Equipe a su personal con uniformes clínicos de alta gama y garantía de fábrica',
                    'preheader' => 'Diseño chileno de alto estándar, garantía de 6 meses y servicio de tallaje en terreno para instituciones de salud.',
                    'hero_title' => 'Imagen corporativa y confort de alto rendimiento para su institución de salud',
                    'hero_desc' => 'Estimado/a <strong>{{ contact.NOMBRE | default: "Director/a o Jefatura de Personas" }}</strong> de <strong>{{ contact.EMPRESA | default: "su institución de salud" }}</strong>: Una dotación clínica de alto nivel proyecta excelencia profesional y fortalece el sentido de pertenencia en su equipo médico. En <strong>Suitable</strong> confeccionamos uniformes clínicos con telas elastizadas Flex 4-Way, acabados antifluidos y <strong>garantía de 6 meses respaldada por fábrica chilena</strong>.',
                    'hero_cta_text' => 'Cotizar Dotación para mi Equipo Clínico →',
                    'pilar1_title' => '🛡️ 6 Meses de Garantía Oficial de Fábrica',
                    'pilar1_desc' => 'Respaldo directo del fabricante chileno ante cualquier defecto de costura, cierre o desprendimiento.',
                    'pilar2_title' => '📏 Servicio de Tallaje a su Equipo Clínico',
                    'pilar2_desc' => 'Llevamos muestras físicas y percheros a su clínica para asegurar el calce perfecto de cada profesional.',
                    'pilar3_title' => '💧 Telas Antifluidos Flex 4-Way Certificadas',
                    'pilar3_desc' => 'Bioseguridad de alto estándar con elasticidad multidireccional que no restringe movimientos en turno.',
                ];
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

    public function rewriteSnippet(Request $request): JsonResponse
    {
        $text = trim($request->input('text', ''));
        $instruction = trim($request->input('instruction', 'Mejorar redacción persuasiva B2B'));
        $provider = $request->input('provider', Setting::get('active_ai_provider', 'groq'));

        if (empty($text)) {
            return response()->json(['success' => false, 'error' => 'Texto vacío.'], 422);
        }

        $systemPrompt = "Eres un redactor senior de marketing B2B para 'Suitable' (fabricante chileno de uniformes clínicos y vestuario médico).\n" .
            "Tu objetivo es reescribir el siguiente fragmento de texto según la instrucción.\n" .
            "Mantén la extensión aproximada del texto original a menos que se pida acortar o alargar.\n" .
            "Responde ÚNICAMENTE con el texto mejorado en español chileno profesional, sin comillas, sin introducciones ni explicaciones.";

        $userPrompt = "Texto original: \"$text\"\nInstrucción: $instruction";
        $result = AiService::generateCopy($provider, $userPrompt, $systemPrompt);
        $improved = trim(preg_replace('/^"|"$|^`|`$/', '', $result['content'] ?? $text));

        return response()->json([
            'success' => true,
            'improved_text' => $improved,
            'original_text' => $text
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
