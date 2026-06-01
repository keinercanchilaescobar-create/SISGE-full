<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Solicitud;
use App\Models\Prestamo;
use App\Models\Equipo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SolicitudController extends Controller
{
    /** GET /api/solicitudes */
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Solicitud::with('usuario:id,nombre,correo')->orderBy('created_at', 'desc');

        if ($user->rol !== 'admin') {
            $query->where('user_id', $user->id);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    /** GET /api/solicitudes/{id} */
    public function show(Request $request, $id)
    {
        $user      = $request->user();
        $solicitud = Solicitud::with('usuario')->find($id);

        if (!$solicitud) {
            return response()->json(['success' => false, 'error' => 'No encontrada.'], 404);
        }

        if ($user->rol !== 'admin' && $solicitud->user_id !== $user->id) {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        return response()->json(['success' => true, 'data' => $solicitud]);
    }

    /**
     * POST /api/solicitudes
     * El estudiante/profesor envía una solicitud de tipo de equipo.
     * No requiere equipo_id específico — pide un tipo y el admin asigna el equipo al aprobar.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_equipo'  => 'required|string|max:50',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
            'motivo'       => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $solicitud = Solicitud::create([
            'user_id'      => $request->user()->id,
            'tipo_equipo'  => $request->tipo_equipo,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
            'motivo'       => $request->motivo,
            'estado'       => 'Pendiente',
        ]);

        return response()->json(['success' => true, 'data' => $solicitud], 201);
    }

    /**
     * PUT /api/solicitudes/{id} — Admin aprueba o rechaza.
     * Si aprueba, debe enviar equipo_id para crear el préstamo automáticamente.
     */
    public function update(Request $request, $id)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $solicitud = Solicitud::find($id);
        if (!$solicitud) {
            return response()->json(['success' => false, 'error' => 'No encontrada.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'estado'    => 'required|in:Aprobada,Rechazada',
            'equipo_id' => 'required_if:estado,Aprobada|exists:equipos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $solicitud->update(['estado' => $request->estado]);

        // Si se aprueba, crear el préstamo automáticamente
        if ($request->estado === 'Aprobada') {
            $equipo = Equipo::find($request->equipo_id);

            if (!$equipo || $equipo->estado !== 'Disponible') {
                return response()->json(['success' => false, 'error' => 'El equipo seleccionado no está disponible.'], 422);
            }

            Prestamo::create([
                'codigo'           => 'PR-' . strtoupper(Str::random(6)),
                'equipo_id'        => $equipo->id,
                'user_id'          => $solicitud->user_id,
                'fecha_prestamo'   => $solicitud->fecha_inicio,
                'fecha_devolucion' => $solicitud->fecha_fin,
                'estado'           => 'Activo',
                'motivo'           => $solicitud->motivo,
            ]);

            $equipo->update(['estado' => 'Prestado']);
        }

        return response()->json(['success' => true, 'data' => $solicitud]);
    }

    /** DELETE /api/solicitudes/{id} */
    public function destroy(Request $request, $id)
    {
        $user      = $request->user();
        $solicitud = Solicitud::find($id);

        if (!$solicitud) {
            return response()->json(['success' => false, 'error' => 'No encontrada.'], 404);
        }

        if ($user->rol !== 'admin' && $solicitud->user_id !== $user->id) {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $solicitud->delete();
        return response()->json(['success' => true, 'message' => 'Solicitud eliminada.']);
    }
}