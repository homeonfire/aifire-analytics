<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manager extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Связь: у менеджера может быть много сделок
    public function deals()
    {
        return $this->hasMany(Deal::class, 'manager_id');
    }

    // Связь со школой (Tenancy)
    public function school()
    {
        return $this->belongsTo(\App\Models\School::class);
    }

    protected $casts = [
        'is_active' => 'boolean', // <-- ДОБАВИЛИ (чтобы Filament понимал это как true/false)
    ];

    public function abcProducts()
    {
        // Хак для Filament: формально связываем менеджера со всеми продуктами школы,
        // а точную фильтрацию (только его сделки) мы сделаем уже внутри таблицы.
        return $this->hasMany(\App\Models\Product::class, 'school_id', 'school_id');
    }
}