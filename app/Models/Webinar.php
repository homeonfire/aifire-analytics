<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webinar extends Model
{
    protected $fillable = [
        'school_id',
        'title',
        'started_at',
        'duration_minutes',
        'room_id',
        'cohort'
    ];

    public function attendances()
    {
        return $this->hasMany(WebinarAttendance::class);
    }

    protected $casts = [
        'started_at' => 'datetime',
    ];

    // Связь со школой (Tenancy)
    public function school()
    {
        return $this->belongsTo(\App\Models\School::class);
    }
}