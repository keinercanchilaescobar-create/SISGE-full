<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mantenimiento;
use App\Models\Equipo;
use Illuminate\Support\Facades\Validator;

class MantenimientoController extends Controller
{
    /** GET /api/mantenimientos */
    public function index(Request $request)
    {
        $query = Mantenimiento::with('equipo')->orderBy('created_at', 'desc');

        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    /** GET /api/mantenimientos/{id} */
    public function show(Request $request, $id)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $mant = Mantenimiento::with('equipo')->find($id);
        if (!$mant) {
            return response()->json(['success' => false, 'error' => 'No encontrado.'], 404);
        }

        return response()->json(['success' => true, 'data' => $mant]);
    }

    /** POST /api/mantenimientos */
    public function store(Request $request)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'equipo_id'           => 'required|exists:equipos,id',
            'tipo_mantenimiento'  => 'required|in:Preventivo,Correctivo,Actualización,Calibración,Otro',
            'descripcion'         => 'required|string',
            'fecha_inicio'        => 'required|date',
            'fecha_fin'           => 'sometimes|date|nullable|after_or_equal:fecha_inicio',
            'tecnico_responsable' => 'sometimes|string|max:100|nullable',
            'notas'               => 'sometimes|string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $mant = Mantenimiento::create($request->only([
            'equipo_id', 'tipo_mantenimiento', 'descripcion',
            'fecha_inicio', 'fecha_fin', 'estado', 'tecnico_responsable', 'notas'
        ]));

        // Actualizar estado del equipo
        Equipo::where('id', $request->equipo_id)->update(['estado' => 'En Mantenimiento']);

        return response()->json(['success' => true, 'data' => $mant], 201);
    }

    /** PUT /api/mantenimientos/{id} */
    public function update(Request $request, $id)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $mant = Mantenimiento::find($id);
        if (!$mant) {
            return response()->json(['success' => false, 'error' => 'No encontrado.'], 404);
        }

        $mant->update($request->only([
            'tipo_mantenimiento', 'descripcion', 'fecha_inicio',
            'fecha_fin', 'estado', 'tecnico_responsable', 'notas'
        ]));

        // Si se completó el mantenimiento, liberar el equipo
        if ($request->estado === 'Completado') {
            Equipo::where('id', $mant->equipo_id)->update(['estado' => 'Disponible']);
        }

        return response()->json(['success' => true, 'data' => $mant]);
    }

    /** DELETE /api/mantenimientos/{id} */
    public function destroy(Request $request, $id)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $mant = Mantenimiento::find($id);
        if (!$mant) {
            return response()->json(['success' => false, 'error' => 'No encontrado.'], 404);
        }

        $mant->delete();
        return response()->json(['success' => true, 'message' => 'Mantenimiento eliminado.']);
    }
}
