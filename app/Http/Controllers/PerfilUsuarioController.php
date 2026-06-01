<?php

namespace App\Http\Controllers;

use App\Models\PerfilUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PerfilUsuarioController extends Controller
{
    public function show(Request $request)
    {
        $user   = $request->user();
        $perfil = PerfilUsuario::where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'data' => [
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

    public function update(Request $request)
    {
        $user   = $request->user();
        $perfil = PerfilUsuario::firstOrCreate(['user_id' => $user->id]);

        $rules = [
            'nombre'      => ['sometimes', 'string', 'max:100'],
            'telefono'    => ['nullable', 'string', 'max:50'],
            'direccion'   => ['nullable', 'string', 'max:255'],
            'carrera'     => ['nullable', 'string', 'max:100'],
            'foto_perfil' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if ($request->filled('nombre')) {
            $user->nombre = $request->input('nombre');
            $user->save();
        }

        $perfil->telefono  = $request->input('telefono');
        $perfil->direccion = $request->input('direccion');
        $perfil->carrera   = $request->input('carrera');

        if ($request->hasFile('foto_perfil')) {
            // Borrar foto anterior de ambas ubicaciones
            if ($perfil->foto_perfil) {
                $fotoAnteriorStorage = storage_path('app/public/perfil/' . $perfil->foto_perfil);
                $fotoAnteriorPublic  = public_path('storage/perfil/' . $perfil->foto_perfil);

                if (file_exists($fotoAnteriorStorage)) {
                    unlink($fotoAnteriorStorage);
                }
                if (file_exists($fotoAnteriorPublic)) {
                    unlink($fotoAnteriorPublic);
                }
            }

            // Guardar nueva foto
            $file     = $request->file('foto_perfil');
            $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();

            $file->storeAs('perfil', $filename, 'public');
            $perfil->foto_perfil = $filename;

            // Copiar automáticamente a public/storage/perfil
            $origen  = storage_path('app/public/perfil/' . $filename);
            $destino = public_path('storage/perfil/' . $filename);

            if (!is_dir(public_path('storage/perfil'))) {
                mkdir(public_path('storage/perfil'), 0755, true);
            }

            copy($origen, $destino);
        }

        $perfil->save();
        $perfil->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado',
            'data' => [
                'telefono'    => $perfil->telefono,
                'direccion'   => $perfil->direccion,
                'carrera'     => $perfil->carrera,
                'foto_perfil' => $perfil->foto_perfil
                    ? 'storage/perfil/' . $perfil->foto_perfil
                    : null,
            ],
        ]);
    }
}