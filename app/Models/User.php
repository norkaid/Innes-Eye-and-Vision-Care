<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const TYPE_CUSTOMER = 'customer';

    public const TYPE_STAFF = 'staff';

    /**
     * Only profile fields are mass assignable. account_type, is_active and roles are
     * always set explicitly by authorised code paths.
     */
    protected $fillable = ['first_name', 'last_name', 'email', 'phone', 'password'];

    protected $hidden = ['password', 'remember_token'];

    /** @var array<string,bool>|null */
    protected ?array $permissionCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    public function isStaff(): bool
    {
        return $this->account_type === self::TYPE_STAFF;
    }

    public function isCustomer(): bool
    {
        return $this->account_type === self::TYPE_CUSTOMER;
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function isSuperAdmin(): bool
    {
        return $this->isStaff() && $this->hasRole('super-admin');
    }

    /** Highest role rank held – used to stop privilege escalation. */
    public function highestRoleRank(): int
    {
        return (int) $this->roles->max('rank');
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->isStaff() || ! $this->is_active) {
            return false;
        }

        if ($this->permissionCache === null) {
            $this->permissionCache = [];
            foreach ($this->roles as $role) {
                foreach ($role->permissions() as $p) {
                    $this->permissionCache[$p] = true;
                }
            }
        }

        return isset($this->permissionCache['*']) || isset($this->permissionCache[$permission]);
    }

    /** @return list<string> */
    public function permissions(): array
    {
        if ($this->isSuperAdmin()) {
            return array_keys(config('innes.permissions'));
        }

        return array_values(array_filter(
            array_keys(config('innes.permissions')),
            fn ($p) => $this->hasPermission($p),
        ));
    }

    public function scopeStaff(Builder $q): Builder
    {
        return $q->where('account_type', self::TYPE_STAFF);
    }

    public function scopeCustomers(Builder $q): Builder
    {
        return $q->where('account_type', self::TYPE_CUSTOMER);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(function (Builder $w) use ($term) {
            $like = '%'.$term.'%';
            $w->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like);
        });
    }
}
