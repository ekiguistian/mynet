<?php

namespace App\Filament\Concerns;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\MaintenanceTicket;
use App\Models\User;
use App\Models\WorkOrder;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseResource extends Resource
{
    protected static string $feature = 'dashboard';

    protected static bool $allowDelete = true;

    public static function canViewAny(): bool
    {
        return static::userCan(false);
    }

    public static function canCreate(): bool
    {
        return static::userCan(true);
    }

    public static function canEdit(Model $record): bool
    {
        return static::userCan(true) && static::recordIsAccessible($record);
    }

    public static function canView(Model $record): bool
    {
        return static::userCan(false) && static::recordIsAccessible($record);
    }

    public static function canDelete(Model $record): bool
    {
        return static::$allowDelete && static::userCan(true) && static::recordIsAccessible($record);
    }

    public static function canDeleteAny(): bool
    {
        return static::$allowDelete && static::userCan(true);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query;
        }

        $model = static::getModel();

        if ($model === Branch::class) {
            if (! $user->isBranchScoped()) {
                return $query;
            }

            return $query->whereKey($user->branch_id);
        }

        if ($model === User::class) {
            return $query
                ->when(! $user->isGlobalAdministrator(), fn (Builder $userQuery) => $userQuery->where('role', '!=', 'administrator'))
                ->when($user->isBranchScoped(), fn (Builder $userQuery) => $userQuery->where('branch_id', $user->branch_id));
        }

        if (! $user->isBranchScoped()) {
            return $query;
        }

        if ($model === Attendance::class && $user->hasRole('technician', 'helper')) {
            return $query->where('user_id', $user->getKey());
        }

        if ($model === WorkOrder::class) {
            if ($user->hasRole('technician', 'helper')) {
                return $query->where('technician_id', $user->getKey());
            }

            if ($user->hasRole('sales')) {
                return $query->where('sales_user_id', $user->getKey());
            }
        }

        if ($model === MaintenanceTicket::class && $user->hasRole('technician', 'helper')) {
            return $query->where('assigned_to', $user->getKey());
        }

        return $query;
    }

    protected static function userCan(bool $write): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessFeature(static::$feature, $write);
    }

    protected static function recordIsAccessible(Model $record): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($record instanceof User) {
            if (! $user->isGlobalAdministrator() && $record->isGlobalAdministrator()) {
                return false;
            }

            return ! $user->isBranchScoped() || (int) $record->branch_id === (int) $user->branch_id;
        }

        if (! $user->isBranchScoped()) {
            return true;
        }

        if ($record instanceof Branch) {
            return (int) $record->getKey() === (int) $user->branch_id;
        }

        if (method_exists($record, 'belongsToBranch')) {
            return $record->belongsToBranch((int) $user->branch_id);
        }

        $branchId = $record->getAttribute('branch_id');

        if ($branchId === null && method_exists($record, 'branch')) {
            $branchId = $record->branch?->getKey();
        }

        return $branchId === null || (int) $branchId === (int) $user->branch_id;
    }
}
