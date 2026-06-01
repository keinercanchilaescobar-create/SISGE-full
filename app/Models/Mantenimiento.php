<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    protected $fillable = [
        'equipo_id',
        'tipo_mantenimiento',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'tecnico_responsable',
        'notas',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }
}