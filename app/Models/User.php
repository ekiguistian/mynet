<?php

namespace App\Models;

use App\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'full_name',
        'username',
        'email',
        'phone',
        'password',
        'role',
        'active',
        'force_password_change',
        'branch_id',
        'customer_id',
        'last_login_at',
        'avatar_path',
        'phone_verified_at',
        'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'force_password_change' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->force_password_change === null) {
                $user->force_password_change = true;
            }
        });

        static::saving(function (User $user): void {
            $actor = auth()->user();

            if (
                $actor instanceof User
                && ! $actor->isGlobalAdministrator()
                && ($user->role === UserRole::Administrator->value || $user->getOriginal('role') === UserRole::Administrator->value)
            ) {
                throw ValidationException::withMessages(['role' => 'Hanya Administrator global yang dapat mengelola akun Administrator.']);
            }

            if ($actor instanceof User && $actor->isBranchScoped()) {
                $user->branch_id = $actor->branch_id;
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->active && UserRole::tryFrom($this->role) !== null;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return filled($this->avatar_path)
            ? Storage::disk('public')->url($this->avatar_path)
            : null;
    }

    public function userRole(): UserRole
    {
        return UserRole::tryFrom($this->role) ?? UserRole::Admin;
    }

    public function hasRole(UserRole|string ...$roles): bool
    {
        foreach ($roles as $role) {
            $roleValue = $role instanceof UserRole ? $role->value : $role;

            if ($this->role === $roleValue) {
                return true;
            }
        }

        return false;
    }

    public function canAccessFeature(string $feature, bool $write = false): bool
    {
        return $this->active && $this->userRole()->canAccessFeature($feature, $write);
    }

    public function isGlobalAdministrator(): bool
    {
        return $this->role === UserRole::Administrator->value;
    }

    public function isBranchScoped(): bool
    {
        return $this->branch_id !== null && ! $this->isGlobalAdministrator();
    }

    public function canAccessBranch(int $branchId): bool
    {
        return ! $this->isBranchScoped() || (int) $this->branch_id === $branchId;
    }

    public function mustCompleteAccountSetup(): bool
    {
        return $this->force_password_change || $this->email_verified_at === null;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'created_by');
    }

    public function salesCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'sales_user_id');
    }

    public function invoicesCreated(): HasMany
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'technician_id');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(StaffEarning::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function maintenanceTickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class, 'assigned_to');
    }

    public function otpChallenges(): HasMany
    {
        return $this->hasMany(OtpChallenge::class);
    }
}
