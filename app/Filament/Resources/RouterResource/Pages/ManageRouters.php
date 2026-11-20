<?php

namespace App\Filament\Resources\RouterResource\Pages;

use App\Filament\Resources\RouterResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageRouters extends ManageRecords
{
    protected static string $resource = RouterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
