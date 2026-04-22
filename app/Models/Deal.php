<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    protected $guarded = [];

    // Связь: Сделка принадлежит одному клиенту
    public function client()
    {
        return $this->belongsTo(UnifiedClient::class, 'unified_client_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Один заказ содержит МНОГО продуктов
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    // Говорим Laravel, что эти поля нужно всегда превращать в объекты даты
    protected $casts = [
        'gc_created_at' => 'datetime',
        'gc_paid_at' => 'datetime',
    ];

    // Связь со школой (Tenancy)
    public function school()
    {
        return $this->belongsTo(\App\Models\School::class);
    }

    public function manager()
    {
        return $this->belongsTo(Manager::class, 'manager_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}