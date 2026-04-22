<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaunchSetting extends Model
{
    protected $fillable = ['school_id', 'cohort', 'date_from', 'date_to'];
}