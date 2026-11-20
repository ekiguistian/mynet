<?php

namespace App\Filament\Resources\FinanceEntryResource\Pages;

use App\Filament\Resources\FinanceEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFinanceEntries extends ManageRecords
{
    protected static string $resource = FinanceEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
