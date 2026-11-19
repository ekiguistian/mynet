<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'full_name', 'username', 'email', 'phone', 'password', 'role', 'active',
        'force_password_change', 'branch_id', 'customer_id', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime', 'last_login_at' => 'datetime',
            'password' => 'hashed', 'active' => 'boolean', 'force_password_change' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->active;
    }

    public function branch() { return $this->belongsTo(Branch::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function createdCustomers() { return $this->hasMany(Customer::class, 'created_by'); }
    public function salesCustomers() { return $this->hasMany(Customer::class, 'sales_user_id'); }
    public function invoicesCreated() { return $this->hasMany(Invoice::class, 'created_by'); }
    public function attendances() { return $this->hasMany(Attendance::class); }
    public function workOrders() { return $this->hasMany(WorkOrder::class, 'technician_id'); }
    public function earnings() { return $this->hasMany(StaffEarning::class); }
    public function inventoryMovements() { return $this->hasMany(InventoryMovement::class); }
    public function maintenanceTickets() { return $this->hasMany(MaintenanceTicket::class, 'assigned_to'); }
}
