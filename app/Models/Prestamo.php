<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestamo extends Model
{
    protected $fillable = [
        'codigo',
        'equipo_id',
        'user_id',
        'fecha_prestamo',
        'fecha_devolucion',
        'estado',
        'motivo',
    ];

    // Sin $casts en fechas: llegan como string "YYYY-MM-DD" desde MySQL,
    // el frontend las formatea con formatFecha() en PrestamoRow.

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}