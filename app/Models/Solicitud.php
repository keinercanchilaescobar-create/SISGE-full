<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $fillable = [
        'user_id',
        'tipo_equipo',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'estado',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}