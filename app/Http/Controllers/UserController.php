<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PerfilUsuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * GET /api/usuarios
     * Acepta ?per_page=N (máx 100, defecto 50).
     * Si se pasa ?all=true devuelve todos (para selects del frontend).
     */
    public function index(Request $request)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        // Los selects de préstamos necesitan la lista completa de usuarios.
        if ($request->boolean('all')) {
            $usuarios = User::select('id', 'nombre', 'correo', 'rol', 'estado')
                ->orderBy('nombre')
                ->get();
            return response()->json(['success' => true, 'data' => $usuarios]);
        }

        $perPage = min((int) $request->query('per_page', 50), 100);

        $usuarios = User::select('users.id', 'users.nombre', 'users.correo', 'users.rol', 'users.estado', 'users.created_at')
            ->addSelect('perfil_usuarios.carrera', 'perfil_usuarios.direccion')
            ->leftJoin('perfil_usuarios', 'perfil_usuarios.user_id', '=', 'users.id')
            ->orderBy('users.nombre')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $usuarios->items(),
            'meta'    => [
                'current_page' => $usuarios->currentPage(),
                'last_page'    => $usuarios->lastPage(),
                'total'        => $usuarios->total(),
                'per_page'     => $usuarios->perPage(),
            ],
        ]);
    }

    /** GET /api/usuarios/{id} — Solo admin */
    public function show(Request $request, $id)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $usuario = User::select('users.id', 'users.nombre', 'users.correo', 'users.rol', 'users.estado', 'users.created_at')
            ->addSelect('perfil_usuarios.carrera', 'perfil_usuarios.direccion')
            ->leftJoin('perfil_usuarios', 'perfil_usuarios.user_id', '=', 'users.id')
            ->where('users.id', $id)
            ->first();

        if (!$usuario) {
            return response()->json(['success' => false, 'error' => 'Usuario no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'data' => $usuario]);
    }

    /** POST /api/usuarios — Solo admin */
    public function store(Request $request)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre'   => 'required|string|max:100',
            'correo'   => 'required|email|unique:users,correo',
            'password' => 'required|string|min:6',
            'rol'      => 'required|in:admin,profesor,estudiante',
            'estado'   => 'sometimes|in:activo,inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $usuario = User::create([
            'nombre'   => $request->nombre,
            'correo'   => $request->correo,
            'password' => Hash::make($request->password),
            'rol'      => $request->rol,
            'estado'   => $request->estado ?? 'activo',
        ]);

        return response()->json(['success' => true, 'data' => $usuario], 201);
    }

    /** PUT /api/usuarios/{id} — Solo admin */
    public function update(Request $request, $id)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        $usuario = User::find($id);
        if (!$usuario) {
            return response()->json(['success' => false, 'error' => 'Usuario no encontrado.'], 404);
        }

        $data = $request->only(['nombre', 'correo', 'rol', 'estado']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $usuario->update($data);

        $perfil = PerfilUsuario::firstOrCreate(['user_id' => $id]);
        $perfil->carrera   = $request->input('carrera');
        $perfil->direccion = $request->input('direccion');
        $perfil->save();

        return response()->json(['success' => true, 'data' => $usuario]);
    }

    /** DELETE /api/usuarios/{id} — Solo admin */
    public function destroy(Request $request, $id)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['success' => false, 'error' => 'No autorizado.'], 403);
        }

        if ($request->user()->id == $id) {
            return response()->json(['success' => false, 'error' => 'No puedes eliminarte a ti mismo.'], 422);
        }

        $usuario = User::find($id);
        if (!$usuario) {
            return response()->json(['success' => false, 'error' => 'Usuario no encontrado.'], 404);
        }

        $usuario->tokens()->delete();
        $usuario->delete();

        return response()->json(['success' => true, 'message' => 'Usuario eliminado.']);
    }
}