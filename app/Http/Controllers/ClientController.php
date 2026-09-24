<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ContactGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status', '');
        $search = trim($request->query('search', ''));
        $groupFilter = (int)$request->query('group', 0);
        $viewMode = $request->query('view', 'table'); // 'table' by default as requested

        $query = Client::query();

        $currentGroup = null;
        if ($groupFilter > 0) {
            $currentGroup = ContactGroup::find($groupFilter);
            if ($currentGroup) {
                $query->whereHas('groups', function($q) use ($groupFilter) {
                    $q->where('contact_groups.id', $groupFilter);
                });
            }
        }

        if ($statusFilter) {
            $query->where('estado', $statusFilter);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('empresa', 'like', "%{$search}%")
                  ->orWhere('contacto_nombre', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('region_comuna', 'like', "%{$search}%");
            });
        }

        $clients = $query->orderBy('updated_at', 'desc')->get();

        // Columns definition for Kanban
        $columns = [
            'nuevo' => [
                'title' => 'Nuevos Prospectos',
                'color' => '#3B82F6',
                'icon' => '📥',
                'step_num' => 1,
                'step_name' => '1. Prospección',
                'step_desc' => 'Entrada y calificación de clínicas o mutuales.',
                'step_action' => 'Validar encargado de compras',
                'clients' => [],
                'total_monto' => 0
            ],
            'correo_1_enviado' => [
                'title' => 'Correo 1 (Flex)',
                'color' => '#6366F1',
                'icon' => '✉️',
                'step_num' => 2,
                'step_name' => '2. Presentación Flex',
                'step_desc' => 'Envío Plantilla 1: Antifluidos y catálogo clínico.',
                'step_action' => 'Presentar telas y tecnología',
                'clients' => [],
                'total_monto' => 0
            ],
            'correo_2_enviado' => [
                'title' => 'Correo 2 (B2B)',
                'color' => '#8B5CF6',
                'icon' => '🚀',
                'step_num' => 3,
                'step_name' => '3. Propuesta B2B',
                'step_desc' => 'Envío Plantilla 2: Fábrica chilena y 6M garantía.',
                'step_action' => 'Ofrecer servicio de tallaje',
                'clients' => [],
                'total_monto' => 0
            ],
            'tallaje_agendado' => [
                'title' => 'Tallaje en Terreno',
                'color' => '#F59E0B',
                'icon' => '📏',
                'step_num' => 4,
                'step_name' => '4. Tallaje en Terreno',
                'step_desc' => '¡Diferenciador Clave! Muestras y percheros in situ.',
                'step_action' => 'Prueba en vivo médicos (XS-3XL)',
                'clients' => [],
                'total_monto' => 0
            ],
            'cotizacion_enviada' => [
                'title' => 'Cotización Enviada',
                'color' => '#10B981',
                'icon' => '💼',
                'step_num' => 5,
                'step_name' => '5. Cotización Formal',
                'step_desc' => 'Propuesta económica por volumen y bordados.',
                'step_action' => 'Seguimiento orden de compra',
                'clients' => [],
                'total_monto' => 0
            ],
            'ganado' => [
                'title' => 'Venta Ganada',
                'color' => '#059669',
                'icon' => '🏆',
                'step_num' => 6,
                'step_name' => '6. Venta Cerrada',
                'step_desc' => 'Institución con orden cerrada o cliente antiguo.',
                'step_action' => 'Fidelización y reposición',
                'clients' => [],
                'total_monto' => 0
            ],
        ];

        $totalPipelineMonto = 0;
        $totalPersonal = 0;
        $totalTallajes = 0;

        foreach ($clients as $c) {
            $st = $c->estado;
            if (isset($columns[$st])) {
                $columns[$st]['clients'][] = $c;
                if ($c->monto_cotizacion) {
                    $columns[$st]['total_monto'] += (float)$c->monto_cotizacion;
                    $totalPipelineMonto += (float)$c->monto_cotizacion;
                }
            }
            if ($c->tamano_equipo) {
                $totalPersonal += (int)$c->tamano_equipo;
            }
            if ($c->fecha_tallaje || $c->estado === 'tallaje_agendado') {
                $totalTallajes++;
            }
        }

        $allGroups = ContactGroup::orderBy('name')->get();

        return view('clients.index', compact(
            'clients',
            'columns',
            'viewMode',
            'statusFilter',
            'search',
            'groupFilter',
            'currentGroup',
            'allGroups',
            'totalPipelineMonto',
            'totalPersonal',
            'totalTallajes'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'empresa' => 'required|string|max:255',
            'contacto_nombre' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telefono' => 'nullable|string|max:100',
            'cargo' => 'nullable|string|max:255',
            'region_comuna' => 'nullable|string|max:255',
            'tamano_equipo' => 'nullable|integer',
            'estado' => 'nullable|string|max:50',
            'notas' => 'nullable|string',
            'group_id' => 'nullable|integer'
        ]);

        $client = Client::create($validated);

        if (!empty($validated['group_id'])) {
            $client->groups()->syncWithoutDetaching([$validated['group_id']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Clínica registrada exitosamente',
            'client' => $client
        ]);
    }

    public function updateStatus(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:nuevo,correo_1_enviado,correo_2_enviado,tallaje_agendado,cotizacion_enviada,ganado,perdido',
            'fecha_tallaje' => 'nullable|date',
            'monto_cotizacion' => 'nullable|numeric'
        ]);

        $client->update([
            'estado' => $validated['status'],
            'fecha_tallaje' => $validated['fecha_tallaje'] ?? $client->fecha_tallaje,
            'monto_cotizacion' => $validated['monto_cotizacion'] ?? $client->monto_cotizacion
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente'
        ]);
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();
        return response()->json([
            'success' => true,
            'message' => 'Contacto eliminado del pipeline'
        ]);
    }
}
