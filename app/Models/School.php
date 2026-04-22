<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class School extends Model
{
    protected $fillable = [
        'name',
        'uuid',
        'getcourse_domain',
        'getcourse_api_key' 
    ];

    // Автоматически генерируем UUID при создании школы
    protected static function booted()
    {
        static::creating(function ($school) {
            if (empty($school->uuid)) {
                $school->uuid = (string) Str::uuid();
            }
        });
    }

    // Сотрудники школы
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'school_user');
    }

    // Дальше прописываем связи со всеми сущностями:
    public function deals() { return $this->hasMany(Deal::class); }
    public function unifiedClients() { return $this->hasMany(UnifiedClient::class); }
    public function products() { return $this->hasMany(Product::class); }
    public function webinars() { return $this->hasMany(Webinar::class); }
    public function managers() { return $this->hasMany(Manager::class); }

    /**
     * Запуски, принадлежащие этой школе.
     */
    public function launches()
    {
        return $this->hasMany(Launch::class);
    }

    public function utmPresets()
    {
        return $this->hasMany(\App\Models\UtmPreset::class);
    }
}