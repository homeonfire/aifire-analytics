<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Launch extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'tripwire_start' => 'datetime',
        'tripwire_end' => 'datetime',
        'booking_start' => 'datetime',
        'booking_end' => 'datetime',
        'flagship_start' => 'datetime',
        'flagship_end' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}