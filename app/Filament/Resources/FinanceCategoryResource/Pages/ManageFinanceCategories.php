<?php

namespace App\Filament\Resources\FinanceCategoryResource\Pages;

use App\Filament\Resources\FinanceCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFinanceCategories extends ManageRecords
{
    protected static string $resource = FinanceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
