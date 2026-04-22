<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnifiedClient extends Model
{
    // Указываем, какие поля разрешено заполнять массово
    protected $fillable = [
        'email',
        'phone',
        'first_name',
        'last_name',
        'city',
        'salebot_id',
        'getcourse_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'total_spent',
        'school_id',
    ];

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    public function webinarAttendances()
    {
        return $this->hasMany(WebinarAttendance::class);
    }

    // Связь с техническими записями о посещении
    public function attendances()
    {
        return $this->hasMany(WebinarAttendance::class);
    }

    // Прямая связь клиента с самими вебинарами через таблицу посещений
    public function webinars()
    {
        return $this->belongsToMany(Webinar::class, 'webinar_attendances');
    }

    // Связь со школой (Tenancy)
    public function school()
    {
        return $this->belongsTo(\App\Models\School::class);
    }
}