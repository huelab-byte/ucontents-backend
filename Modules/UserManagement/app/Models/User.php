<?php

declare(strict_types=1);

namespace Modules\UserManagement\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Modules\Authentication\Notifications\ResetPasswordNotification;
use Modules\Authentication\Notifications\SetPasswordNotification;
use Modules\UserManagement\Models\Role;

/**
 * User Model
 * 
 * Moved from App\Models\User to Modules\UserManagement\Models\User
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasApiTokens, CanResetPassword;

    /**
     * User status constants
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    /**
     * All available statuses
     */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
    ];

    protected $fillable = [
        'name',
        'email',
        'status',
        'is_system',
        'password',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_system' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is a system user (cannot be deleted).
     */
    public function isSystemUser(): bool
    {
        return (bool) $this->is_system;
    }

    /**
     * Check if user can be deleted.
     */
    public function canBeDeleted(): bool
    {
        return !$this->isSystemUser();
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * Get the roles that belong to the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->roles()->whereIn('slug', $roleSlugs)->exists();
    }

    /**
     * Check if user has a specific permission.
     * Super admin has all permissions.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // Super admin has all permissions
        if ($this->hasRole('super_admin')) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })
            ->exists();
    }

    /**
     * Check if user has any of the given permissions.
     * Super admin has all permissions.
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        // Super admin has all permissions
        if ($this->hasRole('super_admin')) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlugs) {
                $query->whereIn('slug', $permissionSlugs);
            })
            ->exists();
    }

    /**
     * Check if user is an admin (has admin or super_admin role).
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Check if user account is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if user account is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Suspend the user account.
     */
    public function suspend(): void
    {
        $this->update(['status' => self::STATUS_SUSPENDED]);
    }

    /**
     * Activate the user account.
     */
    public function activate(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(string $roleSlug): void
    {
        $role = Role::where('slug', $roleSlug)->first();
        if ($role && !$this->hasRole($roleSlug)) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string $roleSlug): void
    {
        $role = Role::where('slug', $roleSlug)->first();
        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * Sync user roles.
     */
    public function syncRoles(array $roleSlugs): void
    {
        $roleIds = Role::whereIn('slug', $roleSlugs)->pluck('id')->toArray();
        $this->roles()->sync($roleIds);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Send the magic link notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendMagicLinkNotification(string $token): void
    {
        $this->notify(new \Modules\Authentication\Notifications\MagicLinkNotification($token, $this->email));
    }

    /**
     * Send the set password notification for new users.
     *
     * @param  string  $token
     * @return void
     */
    public function sendSetPasswordNotification(string $token): void
    {
        $this->notify(new SetPasswordNotification($token));
    }

}
