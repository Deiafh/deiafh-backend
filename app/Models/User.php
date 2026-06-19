<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'password',
        'username',
        'image',
        'hidden',
        'last_seen_at',
        'current_page',
    ];

    // A user is considered online if seen within this many seconds.
    public const ONLINE_THRESHOLD_SECONDS = 90;

    protected $appends = ['image_url'];

    // Roles/permissions live under the JWT 'api' guard; pin it so Gate checks
    // (can()/canAny() used by the permission middleware) resolve correctly.
    protected string $guard_name = 'api';

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
            'hidden' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function getImageUrlAttribute()
    {
        return url($this->image);
    }

    public function getIsOnlineAttribute(): bool
    {
        return $this->last_seen_at
            && $this->last_seen_at->gt(now()->subSeconds(self::ONLINE_THRESHOLD_SECONDS));
    }

    /**
     * Branches this user is allowed to see orders for.
     * No assigned branches means full access (all branches).
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class);
    }

    /**
     * Allowed branch ids for order scoping. An empty array means "all branches".
     *
     * @return array<int>
     */
    public function allowedBranchIds(): array
    {
        return $this->branches()->pluck('branches.id')->all();
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
