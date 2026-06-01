<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    protected $fillable = [
        'codigo',
        'tipo',
        'marca',
        'modelo',
        'estado',
        'descripcion',
    ];

    public function prestamos()
    {
        return $this->hasMany(Prestamo::class);
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class);
    }
}