<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prestamo;
use App\Models\Equipo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PrestamoController extends Controller
{
    /**
     * GET /api/prestamos
     * Acepta ?per_page=N (máx 100, defecto 15).
     * Admin ve todos; profesor/estudiante solo los suyos.
     */
    public function index(Request $request)
    {
        $user    = $request->user();
        $perPage = min((int) $request->query('per_page', 15), 100);

        $query = Prestamo::with(['equipo', 'usuario'])
            ->orderBy('created_at', 'desc');

        if ($user->rol !== 'admin') {
            $query->where('user_id', $user->id);
        }

        $prestamos = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => array_map([$this, 'formatPrestamo'], $prestamos->items()),
            'meta'    => [
                'current_page' => $prestamos->currentPage(),
                'last_page'    => $prestamos->lastPage(),
                'total'        => $prestamos->total(),
                'per_page'     => $prestamos->perPage(),
            ],
        ]);
    }

    /** GET /api/prestamos/{id} */
    public function show(Request $request, $id)
    {
        $user     = $request->user();
        $prestamo = Prestamo::with(['equipo', 'usuario'])->find($id);

        if (!$prestamo) {
            return response()->json(['success' => false, 'error' => 'Préstamo no encontrado.'], 404);
        }

        if ($user->rol !== 'admin' && $prestamo->user_id !== $user->id) {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        return response()->json(['success' => true, 'data' => $this->formatPrestamo($prestamo)]);
    }

    /**
     * POST /api/prestamos — Crear préstamo directo (solo admin)
     */
    public function store(Request $request)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'equipo_id'        => 'required|exists:equipos,id',
            'user_id'          => 'required|exists:users,id',
            'fecha_prestamo'   => 'required|date',
            'fecha_devolucion' => 'required|date|after_or_equal:fecha_prestamo',
            'motivo'           => 'sometimes|string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $equipo = Equipo::find($request->equipo_id);
        if ($equipo->estado !== 'Disponible') {
            return response()->json(['success' => false, 'error' => 'El equipo no está disponible.'], 422);
        }

        $prestamo = Prestamo::create([
            'codigo'           => 'PR-' . strtoupper(Str::random(6)),
            'equipo_id'        => $request->equipo_id,
            'user_id'          => $request->user_id,
            'fecha_prestamo'   => $request->fecha_prestamo,
            'fecha_devolucion' => $request->fecha_devolucion,
            'estado'           => 'Activo',
            'motivo'           => $request->motivo,
        ]);

        $equipo->update(['estado' => 'Prestado']);

        return response()->json([
            'success' => true,
            'data'    => $this->formatPrestamo($prestamo->load(['equipo', 'usuario'])),
        ], 201);
    }

    /**
     * PUT /api/prestamos/{id} — Admin actualiza estado del préstamo
     */
    public function update(Request $request, $id)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $prestamo = Prestamo::with('equipo')->find($id);
        if (!$prestamo) {
            return response()->json(['success' => false, 'error' => 'Préstamo no encontrado.'], 404);
        }

        $prestamo->update($request->only(['estado', 'motivo', 'fecha_devolucion']));

        if ($request->estado === 'Activo') {
            $prestamo->equipo?->update(['estado' => 'Prestado']);
        }

        if (in_array($request->estado, ['Devuelto', 'Cancelado'])) {
            $prestamo->equipo?->update(['estado' => 'Disponible']);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatPrestamo($prestamo->fresh(['equipo', 'usuario'])),
        ]);
    }

    /**
     * POST /api/prestamos/cancelar/{id}
     * El usuario cancela su propio préstamo pendiente/activo.
     */
    public function cancelar(Request $request, $id)
    {
        $user     = $request->user();
        $prestamo = Prestamo::with('equipo')->find($id);

        if (!$prestamo) {
            return response()->json(['success' => false, 'error' => 'Préstamo no encontrado.'], 404);
        }

        if ($user->rol !== 'admin' && $prestamo->user_id !== $user->id) {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $prestamo->update(['estado' => 'Cancelado']);

        if ($prestamo->getOriginal('estado') === 'Activo') {
            $prestamo->equipo?->update(['estado' => 'Disponible']);
        }

        return response()->json(['success' => true, 'message' => 'Préstamo cancelado.']);
    }

    /**
     * DELETE /api/prestamos/{id} — Solo admin
     */
    public function destroy(Request $request, $id)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $prestamo = Prestamo::with('equipo')->find($id);

        if (!$prestamo) {
            return response()->json(['success' => false, 'error' => 'Préstamo no encontrado.'], 404);
        }

        if (in_array($prestamo->estado, ['Activo', 'Pendiente'])) {
            $prestamo->equipo?->update(['estado' => 'Disponible']);
        }

        $prestamo->delete();

        return response()->json(['success' => true, 'message' => 'Préstamo eliminado.']);
    }

    // ─── Rutas de compatibilidad con el frontend ─────────────────────────────

    /** GET /api/estudiante/prestamos  y  GET /api/profesor/prestamos */
    public function misPrestamosPorRol(Request $request)
    {
        $prestamos = Prestamo::with('equipo')
            ->where('user_id', $request->user()->id)
            ->whereIn('estado', ['Pendiente', 'Activo'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($p) => $this->formatPrestamo($p));

        return response()->json($prestamos);
    }

    /** GET /api/estudiante/historial  y  GET /api/profesor/historial */
    public function historialPorRol(Request $request)
    {
        $historial = Prestamo::with('equipo')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($p) => $this->formatPrestamo($p));

        return response()->json($historial);
    }

    /**
     * POST /api/prestamos/solicitar — Estudiante/Profesor solicitan equipo
     */
    public function solicitar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'equipo_id'       => 'required|exists:equipos,id',
            'fecha_devolucion' => 'required|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $equipo = Equipo::find($request->equipo_id);
        if ($equipo->estado !== 'Disponible') {
            return response()->json(['success' => false, 'error' => 'El equipo no está disponible.'], 422);
        }

        $prestamo = Prestamo::create([
            'codigo'           => 'PR-' . strtoupper(Str::random(6)),
            'equipo_id'        => $request->equipo_id,
            'user_id'          => $request->user()->id,
            'fecha_prestamo'   => now()->toDateString(),
            'fecha_devolucion' => $request->fecha_devolucion,
            'estado'           => 'Pendiente',
            'motivo'           => $request->motivo ?? 'Solicitud desde panel',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $this->formatPrestamo($prestamo->load('equipo')),
        ], 201);
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function formatPrestamo(Prestamo $p): array
    {
        return [
            'id'               => $p->id,
            'codigo'           => $p->codigo,
            'equipo_id'        => $p->equipo_id,
            'user_id'          => $p->user_id,
            'estado'           => $p->estado,
            'motivo'           => $p->motivo,
            'fecha_prestamo'   => $p->fecha_prestamo,
            'fecha_devolucion' => $p->fecha_devolucion,
            'created_at'       => $p->created_at,
            'equipo_codigo'    => $p->equipo?->codigo,
            'equipo_tipo'      => $p->equipo?->tipo,
            'equipo_marca'     => $p->equipo?->marca,
            'equipo_modelo'    => $p->equipo?->modelo,
            'equipo'           => trim(($p->equipo?->tipo ?? '') . ' ' . ($p->equipo?->marca ?? '') . ' ' . ($p->equipo?->modelo ?? '')),
            'usuario_nombre'   => $p->usuario?->nombre,
        ];
    }
}