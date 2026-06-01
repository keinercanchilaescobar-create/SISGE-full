<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PerfilUsuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo'   => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error'   => 'Correo y contraseña son requeridos.',
            ], 422);
        }

        $user = User::where('correo', $request->correo)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'error'   => 'Credenciales incorrectas.',
            ], 401);
        }

        if ($user->estado === 'inactivo') {
            return response()->json([
                'success' => false,
                'error'   => 'Tu cuenta está desactivada.',
            ], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('sisge-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'         => $user->id,
                'nombre'     => $user->nombre,
                'correo'     => $user->correo,
                'rol'        => $user->rol,
                'estado'     => $user->estado,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre'   => 'required|string|max:100',
            'correo'   => 'required|email|unique:users,correo',
            'password' => 'required|string|min:6',
            'rol'      => 'sometimes|in:admin,profesor,estudiante',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error'   => $validator->errors()->first(),
            ], 422);
        }

        $user = User::create([
            'nombre'   => $request->nombre,
            'correo'   => $request->correo,
            'password' => Hash::make($request->password),
            'rol'      => $request->rol ?? 'estudiante',
            'estado'   => 'activo',
        ]);

        PerfilUsuario::create(['user_id' => $user->id]);

        $token = $user->createToken('sisge-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'     => $user->id,
                'nombre' => $user->nombre,
                'correo' => $user->correo,
                'rol'    => $user->rol,
                'estado' => $user->estado,
            ],
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada.',
        ]);
    }

    public function me(Request $request)
    {
        $user   = $request->user();
        $perfil = PerfilUsuario::where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'          => $user->id,
                'nombre'      => $user->nombre,
                'correo'      => $user->correo,
                'rol'         => $user->rol,
                'estado'      => $user->estado,
                'created_at'  => $user->created_at,
                'telefono'    => $perfil?->telefono,
                'direccion'   => $perfil?->direccion,
                'carrera'     => $perfil?->carrera,
                'foto_perfil' => $perfil?->foto_perfil
                    ? 'storage/perfil/' . $perfil->foto_perfil
                    : null,
            ],
        ]);
    }
}