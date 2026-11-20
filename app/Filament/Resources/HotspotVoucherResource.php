<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\BaseResource;
use App\Filament\Forms\BranchField;
use App\Filament\Resources\HotspotVoucherResource\Pages;
use App\Models\HotspotVoucher;
use App\Models\Router;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class HotspotVoucherResource extends BaseResource
{
    protected static ?string $model = HotspotVoucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Jaringan & MikroTik';

    protected static ?string $navigationLabel = 'Voucher Hotspot';

    protected static string $feature = 'vouchers';

    public static function form(Form $form): Form
    {
        return $form->schema([
            BranchField::make(),
            Forms\Components\Select::make('router_id')->label('Router')->options(fn (): array => Router::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all())->searchable(),
            Forms\Components\TextInput::make('profile')->label('Profil MikroTik')->required()->maxLength(80),
            Forms\Components\TextInput::make('username')->label('Username voucher')->required()->maxLength(100),
            Forms\Components\TextInput::make('password')->label('Password voucher')->password()->revealable()->formatStateUsing(fn (): null => null)->required(fn (string $operation): bool => $operation === 'create')->dehydrated(fn (?string $state): bool => filled($state)),
            Forms\Components\Select::make('status')->label('Status')->options(['ready' => 'Siap', 'sold' => 'Terjual', 'active' => 'Aktif', 'expired' => 'Kedaluwarsa', 'disabled' => 'Nonaktif'])->required()->default('ready'),
            Forms\Components\TextInput::make('unit_price')->label('Harga jual')->numeric()->prefix('Rp')->minValue(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('username')->label('Username')->searchable(),
            Tables\Columns\TextColumn::make('profile')->label('Profil'),
            Tables\Columns\TextColumn::make('router.name')->label('Router')->placeholder('Belum dipilih'),
            Tables\Columns\TextColumn::make('branch.name')->label('Cabang'),
            Tables\Columns\TextColumn::make('unit_price')->label('Harga')->money('IDR'),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
            Tables\Columns\TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable(),
        ])->filters([Tables\Filters\SelectFilter::make('status')->options(['ready' => 'Siap', 'sold' => 'Terjual', 'active' => 'Aktif', 'expired' => 'Kedaluwarsa', 'disabled' => 'Nonaktif'])])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageHotspotVouchers::route('/')];
    }
}
