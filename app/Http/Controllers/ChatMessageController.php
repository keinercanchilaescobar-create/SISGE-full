<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Validator;

class ChatMessageController extends Controller
{
    /**
     * GET /api/chat
     */
    public function index()
    {
        $mensajes = ChatMessage::with('usuario:id,nombre')
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get()
            ->map(fn($m) => [
                'id'         => $m->id,
                'user_id'    => $m->user_id,
                'nombre'     => $m->usuario?->nombre ?? 'Anónimo',
                'message'    => $m->message,
                'created_at' => $m->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $mensajes,
        ]);
    }

    /**
     * POST /api/chat
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $mensaje = ChatMessage::create([
            'user_id' => $request->user()->id,
            'message' => $request->message,
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'         => $mensaje->id,
                'user_id'    => $mensaje->user_id,
                'nombre'     => $request->user()->nombre,
                'message'    => $mensaje->message,
                'created_at' => $mensaje->created_at,
            ],
        ], 201);
    }
}