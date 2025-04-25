<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $primaryKey = 'uuid';
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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

    public function getFullNameAttribute(): string
    {
        return "{$this->name} {$this->last_name}";
    }

    public function getRoleAttribute(): ?string
    {
        $roleName = $this->roles->first()?->name;
        return $roleName;
    }

    public function getRoleNameAttribute(): ?string
    {
        $roleName = $this->roles->first()?->name;
        return match ($roleName) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            default => $roleName
        };
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', '%'.$term.'%')
                     ->orWhere('email', 'like', '%'.$term.'%')
                     ->orWhere('last_name', 'like', '%'.$term.'%');
    }
}
