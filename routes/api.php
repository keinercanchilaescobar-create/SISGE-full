<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PerfilUsuarioController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\SolicitudController;

/*
|--------------------------------------------------------------------------
| SISGE API ROUTES
|--------------------------------------------------------------------------
*/

# ─────────────────────────────────────────────
# AUTH (PÚBLICO)
# ─────────────────────────────────────────────
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

# ─────────────────────────────────────────────
# PROTEGIDAS (SANCTUM)
# ─────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    # ───── AUTH ─────
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    # ─────────────────────────────
    # EQUIPOS
    # ─────────────────────────────
    Route::get('/equipos',              [EquipoController::class, 'index']);
    Route::get('/equipos/disponibles',  [EquipoController::class, 'disponibles']); // ANTES de /{id}
    Route::get('/equipos/{id}',         [EquipoController::class, 'show']);
    Route::post('/equipos',             [EquipoController::class, 'store']);
    Route::put('/equipos/{id}',         [EquipoController::class, 'update']);
    Route::delete('/equipos/{id}',      [EquipoController::class, 'destroy']);

    # ─────────────────────────────
    # PRÉSTAMOS
    # IMPORTANTE: rutas con segmentos fijos ANTES de las que usan {id}
    # ─────────────────────────────
    Route::get('/prestamos',                    [PrestamoController::class, 'index']);
    Route::post('/prestamos/solicitar',         [PrestamoController::class, 'solicitar']);   // ANTES de /{id}
    Route::post('/prestamos/cancelar/{id}',     [PrestamoController::class, 'cancelar']);    // ANTES de /{id}
    Route::post('/prestamos',                   [PrestamoController::class, 'store']);
    Route::get('/prestamos/{id}',               [PrestamoController::class, 'show']);
    Route::put('/prestamos/{id}',               [PrestamoController::class, 'update']);
    Route::delete('/prestamos/{id}',            [PrestamoController::class, 'destroy']);

    Route::get('/estudiante/prestamos',         [PrestamoController::class, 'misPrestamosPorRol']);
    Route::get('/estudiante/historial',         [PrestamoController::class, 'historialPorRol']);
    Route::get('/profesor/prestamos',           [PrestamoController::class, 'misPrestamosPorRol']);
    Route::get('/profesor/historial',           [PrestamoController::class, 'historialPorRol']);

    # ─────────────────────────────
    # USUARIOS (ADMIN)
    # ─────────────────────────────
    Route::get('/usuarios',         [UserController::class, 'index']);
    Route::get('/usuarios/{id}',    [UserController::class, 'show']);
    Route::post('/usuarios',        [UserController::class, 'store']);
    Route::put('/usuarios/{id}',    [UserController::class, 'update']);
    Route::delete('/usuarios/{id}', [UserController::class, 'destroy']);

    # ─────────────────────────────
    # CHAT
    # ─────────────────────────────
    Route::get('/chat',  [ChatMessageController::class, 'index']);
    Route::post('/chat', [ChatMessageController::class, 'store']);

    # ─────────────────────────────
    # PERFIL
    # ─────────────────────────────
    Route::get('/perfil',             [PerfilUsuarioController::class, 'show']);
    Route::post('/perfil/actualizar', [PerfilUsuarioController::class, 'update']);

    # ─────────────────────────────
    # MANTENIMIENTOS
    # ─────────────────────────────
    Route::get('/mantenimientos',         [MantenimientoController::class, 'index']);
    Route::get('/mantenimientos/{id}',    [MantenimientoController::class, 'show']);
    Route::post('/mantenimientos',        [MantenimientoController::class, 'store']);
    Route::put('/mantenimientos/{id}',    [MantenimientoController::class, 'update']);
    Route::delete('/mantenimientos/{id}', [MantenimientoController::class, 'destroy']);

    # ─────────────────────────────
    # SOLICITUDES
    # ─────────────────────────────
    Route::get('/solicitudes',         [SolicitudController::class, 'index']);
    Route::get('/solicitudes/{id}',    [SolicitudController::class, 'show']);
    Route::post('/solicitudes',        [SolicitudController::class, 'store']);
    Route::put('/solicitudes/{id}',    [SolicitudController::class, 'update']);
    Route::delete('/solicitudes/{id}', [SolicitudController::class, 'destroy']);
});