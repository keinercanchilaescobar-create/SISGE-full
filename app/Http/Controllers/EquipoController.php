<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipo;
use Illuminate\Support\Facades\Validator;

class EquipoController extends Controller
{
    /**
     * GET /api/equipos
     * Acepta ?per_page=N (máx 100, defecto 50).
     * Si se pasa ?all=true devuelve todos (solo para selects del frontend).
     */
    public function index(Request $request)
    {
        // Los selects del dashboard necesitan la lista completa para elegir equipo.
        // Para la tabla paginamos.
        if ($request->boolean('all')) {
            $equipos = Equipo::orderBy('tipo')->get();
            return response()->json(['success' => true, 'data' => $equipos]);
        }

        $perPage = min((int) $request->query('per_page', 50), 100);

        $equipos = Equipo::orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $equipos->items(),
            'meta'    => [
                'current_page' => $equipos->currentPage(),
                'last_page'    => $equipos->lastPage(),
                'total'        => $equipos->total(),
                'per_page'     => $equipos->perPage(),
            ],
        ]);
    }

    /** GET /api/equipos/disponibles — Solo equipos con estado Disponible (para selects) */
    public function disponibles()
    {
        $equipos = Equipo::where('estado', 'Disponible')
            ->orderBy('tipo')
            ->get();

        return response()->json(['success' => true, 'data' => $equipos]);
    }

    /** GET /api/equipos/{id} */
    public function show($id)
    {
        $equipo = Equipo::find($id);

        if (!$equipo) {
            return response()->json(['success' => false, 'error' => 'Equipo no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'data' => $equipo]);
    }

    /** POST /api/equipos — Crear equipo (solo admin) */
    public function store(Request $request)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'codigo'      => 'required|string|unique:equipos,codigo',
            'tipo'        => 'required|string|max:50',
            'marca'       => 'required|string|max:50',
            'modelo'      => 'required|string|max:50',
            'estado'      => 'sometimes|in:Disponible,Prestado,En Mantenimiento,Fuera de Servicio,No Disponible',
            'descripcion' => 'sometimes|string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $equipo = Equipo::create($request->only(['codigo', 'tipo', 'marca', 'modelo', 'estado', 'descripcion']));

        return response()->json(['success' => true, 'data' => $equipo], 201);
    }

    /** PUT /api/equipos/{id} — Actualizar equipo (solo admin) */
    public function update(Request $request, $id)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $equipo = Equipo::find($id);
        if (!$equipo) {
            return response()->json(['success' => false, 'error' => 'Equipo no encontrado.'], 404);
        }

        $equipo->update($request->only(['codigo', 'tipo', 'marca', 'modelo', 'estado', 'descripcion']));

        return response()->json(['success' => true, 'data' => $equipo]);
    }

    /** DELETE /api/equipos/{id} — Eliminar equipo (solo admin) */
    public function destroy(Request $request, $id)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $equipo = Equipo::find($id);
        if (!$equipo) {
            return response()->json(['success' => false, 'error' => 'Equipo no encontrado.'], 404);
        }

        $equipo->delete();

        return response()->json(['success' => true, 'message' => 'Equipo eliminado.']);
    }
}