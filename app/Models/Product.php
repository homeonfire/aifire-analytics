<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    // Один продукт может быть во многих заказах
    // ИСПРАВЛЕННАЯ СВЯЗЬ: Продукты и сделки связаны через промежуточную таблицу (many-to-many)
    public function deals()
    {
        return $this->belongsToMany(Deal::class);
    }

    // Связь со школой (Tenancy)
    public function school()
    {
        return $this->belongsTo(\App\Models\School::class);
    }

    public function launches()
    {
        return $this->belongsToMany(Launch::class);
    }
}
