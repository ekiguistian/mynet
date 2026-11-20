<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\BaseResource;
use App\Filament\Forms\BranchField;
use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends BaseResource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-wifi';

    protected static ?string $navigationGroup = 'Manajemen Pelanggan';

    protected static ?string $navigationLabel = 'Paket Internet';

    protected static ?string $modelLabel = 'paket internet';

    protected static ?string $pluralModelLabel = 'paket internet';

    protected static string $feature = 'plans';

    public static function form(Form $form): Form
    {
        return $form->schema([
            BranchField::make(required: false, includeShared: true),
            Forms\Components\TextInput::make('name')->label('Nama paket')->required()->maxLength(120),
            Forms\Components\Select::make('service_type')->label('Jenis layanan')->options([
                'pppoe' => 'PPPoE', 'static_ip' => 'IP Static', 'hotspot' => 'Hotspot',
            ])->required()->default('pppoe'),
            Forms\Components\TextInput::make('speed_download')->label('Kecepatan unduh')->maxLength(30),
            Forms\Components\TextInput::make('speed_upload')->label('Kecepatan unggah')->maxLength(30),
            Forms\Components\TextInput::make('monthly_price')->label('Harga bulanan')->numeric()->prefix('Rp')->required()->default(0),
            Forms\Components\TextInput::make('hotspot_profile')->label('Profil RouterOS')->maxLength(80)->visible(fn (Forms\Get $get): bool => in_array($get('service_type'), ['pppoe', 'hotspot'], true)),
            Forms\Components\Toggle::make('active')->label('Aktif')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Paket')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('branch.name')->label('Cabang')->placeholder('Semua cabang'),
            Tables\Columns\TextColumn::make('service_type')->label('Layanan')->badge(),
            Tables\Columns\TextColumn::make('speed_download')->label('Unduh'),
            Tables\Columns\TextColumn::make('speed_upload')->label('Unggah'),
            Tables\Columns\TextColumn::make('monthly_price')->label('Harga/bulan')->money('IDR'),
            Tables\Columns\IconColumn::make('active')->label('Aktif')->boolean(),
        ])->filters([
            Tables\Filters\SelectFilter::make('service_type')->options(['pppoe' => 'PPPoE', 'static_ip' => 'IP Static', 'hotspot' => 'Hotspot']),
            Tables\Filters\TernaryFilter::make('active')->label('Status'),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePlans::route('/')];
    }
}
