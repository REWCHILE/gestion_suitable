<?php

namespace App\Http\Controllers;

use App\Models\ContactGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function index(): View
    {
        $groups = ContactGroup::withCount('clients')
            ->orderBy('id', 'desc')
            ->get();

        return view('groups.index', compact('groups'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50'
        ]);

        $group = ContactGroup::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'color' => $validated['color'] ?: '#1E8888'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grupo creado exitosamente',
            'group' => $group
        ]);
    }

    public function destroy(ContactGroup $group): JsonResponse
    {
        $group->delete();
        return response()->json([
            'success' => true,
            'message' => 'Grupo eliminado correctamente'
        ]);
    }
}
