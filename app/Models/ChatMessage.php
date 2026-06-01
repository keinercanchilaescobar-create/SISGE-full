<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['user_id', 'message'];

    /**
     * Relación con el usuario que envió el mensaje.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}