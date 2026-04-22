<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TildaLead extends Model
{
    protected $guarded = []; // Разрешаем писать во все колонки

    // Связь: один лид из Тильды принадлежит одному Единому Клиенту
    public function client()
    {
        return $this->belongsTo(UnifiedClient::class, 'unified_client_id');
    }

    // Связь со школой (Tenancy)
    public function school()
    {
        return $this->belongsTo(\App\Models\School::class);
    }
}
