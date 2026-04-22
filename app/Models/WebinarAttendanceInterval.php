<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebinarAttendanceInterval extends Model
{
    protected $fillable = ['attendance_id', 'entered_at', 'left_at', 'minutes'];

    public function attendance()
    {
        return $this->belongsTo(WebinarAttendance::class);
    }
}