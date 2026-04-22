<?php

namespace App\Models;

// ВОТ ЭТИХ СТРОК НЕ ХВАТАЛО:
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class User extends Authenticatable implements HasTenants, FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- ЛОГИКА ШКОЛ (TENANCY) ---

    // Связь со школами
    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_user');
    }

    // Возвращаем список школ, к которым у пользователя есть доступ
    public function getTenants(Panel $panel): Collection
    {
        return $this->schools;
    }

    // Может ли юзер зайти в конкретную школу?
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->schools()->whereKey($tenant)->exists();
    }

    // Разрешаем вход в админку
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}