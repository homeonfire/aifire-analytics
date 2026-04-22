<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GcExport extends Model
{
    protected $fillable = [
        'school_id',
        'export_id',
        'date_from',
        'date_to',
        'status',
        'error_message',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}