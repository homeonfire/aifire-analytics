<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebinarChatMessage extends Model
{
    protected $fillable = ['attendance_id', 'time', 'message'];

    public function attendance()
    {
        return $this->belongsTo(WebinarAttendance::class);
    }
}