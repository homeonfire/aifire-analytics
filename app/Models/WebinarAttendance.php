<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebinarAttendance extends Model
{
    protected $fillable = [
        'webinar_id',
        'unified_client_id',
        'city',
        'device',
        'clicked_button',
        'clicked_banner',
        'total_minutes'
    ];

    public function webinar()
    {
        return $this->belongsTo(Webinar::class);
    }

    public function client()
    {
        return $this->belongsTo(UnifiedClient::class, 'unified_client_id');
    }

    public function intervals()
    {
        return $this->hasMany(WebinarAttendanceInterval::class, 'attendance_id');
    }

    public function chatMessages()
    {
        return $this->hasMany(WebinarChatMessage::class, 'attendance_id');
    }
}