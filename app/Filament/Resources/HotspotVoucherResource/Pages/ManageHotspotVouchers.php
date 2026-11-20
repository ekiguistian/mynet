<?php

namespace App\Filament\Resources\HotspotVoucherResource\Pages;

use App\Filament\Resources\HotspotVoucherResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageHotspotVouchers extends ManageRecords
{
    protected static string $resource = HotspotVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
