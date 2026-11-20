<?php

namespace App\Filament\Forms;

use App\Models\Branch;
use App\Models\User;
use Filament\Forms\Components\Select;

final class BranchField
{
    public static function make(bool $required = true, bool $includeShared = false): Select
    {
        return Select::make('branch_id')
            ->label('Cabang')
            ->options(function () use ($includeShared): array {
                $user = auth()->user();

                if ($user instanceof User && $user->isBranchScoped()) {
                    return Branch::query()->whereKey($user->branch_id)->pluck('name', 'id')->all();
                }

                $options = Branch::query()->orderBy('name')->pluck('name', 'id')->all();

                if ($includeShared) {
                    $options = ['' => 'Semua cabang'] + $options;
                }

                return $options;
            })
            ->searchable()
            ->preload()
            ->required($required)
            ->nullable(! $required || $includeShared)
            ->default(fn (): ?int => auth()->user()?->branch_id)
            ->disabled(fn (): bool => auth()->user() instanceof User && auth()->user()->isBranchScoped())
            ->dehydrated();
    }
}
