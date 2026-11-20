<?php

namespace App\Filament\Concerns;

use App\Models\User;

trait CanManageApplicationSettings
{
    protected static function canManage(string $feature): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessFeature($feature, true);
    }
}
