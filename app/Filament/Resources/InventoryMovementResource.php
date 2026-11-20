<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\BaseResource;
use App\Filament\Forms\BranchField;
use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Models\InventoryMovement;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryMovementResource extends BaseResource
{
    protected static ?string $model = InventoryMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Inventaris';

    protected static ?string $navigationLabel = 'Barang Masuk/Keluar';

    protected static string $feature = 'inventory';

    protected static bool $allowDelete = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            BranchField::make(),
            Forms\Components\Select::make('product_id')->label('Barang')->options(fn (): array => Product::query()->where('active', true)->orderBy('name')->get()->mapWithKeys(fn (Product $product): array => [$product->id => $product->name.' (stok '.$product->stock_qty.' '.$product->unit.')'])->all())->searchable()->preload()->required()->live()->afterStateUpdated(fn (Forms\Set $set, $state) => $set('branch_id', Product::query()->whereKey($state)->value('branch_id'))),
            Forms\Components\Select::make('direction')->label('Jenis pergerakan')->options(['in' => 'Barang masuk', 'out' => 'Barang keluar'])->required()->default('in'),
            Forms\Components\TextInput::make('quantity')->label('Jumlah')->numeric()->required()->minValue(0.01),
            Forms\Components\TextInput::make('unit_cost')->label('Harga satuan/modal')->numeric()->prefix('Rp')->default(0)->required(),
            Forms\Components\TextInput::make('reason')->label('Keperluan')->maxLength(160),
            Forms\Components\TextInput::make('reference')->label('Nomor referensi')->maxLength(80),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->label('Waktu')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('product.name')->label('Barang')->searchable(),
            Tables\Columns\TextColumn::make('branch.name')->label('Cabang')->placeholder('Semua cabang'),
            Tables\Columns\TextColumn::make('direction')->label('Arah')->badge()->formatStateUsing(fn (string $state): string => $state === 'in' ? 'Masuk' : 'Keluar')->color(fn (string $state): string => $state === 'in' ? 'success' : 'warning'),
            Tables\Columns\TextColumn::make('quantity')->label('Jumlah')->numeric(decimalPlaces: 2),
            Tables\Columns\TextColumn::make('unit_cost')->label('Harga satuan')->money('IDR'),
            Tables\Columns\TextColumn::make('reason')->label('Keperluan'),
            Tables\Columns\TextColumn::make('user.name')->label('Dicatat oleh'),
        ])->filters([Tables\Filters\SelectFilter::make('direction')->options(['in' => 'Barang masuk', 'out' => 'Barang keluar']), Tables\Filters\Filter::make('created_at')->form([Forms\Components\DatePicker::make('from')->label('Dari'), Forms\Components\DatePicker::make('until')->label('Sampai')])->query(fn ($query, array $data) => $query->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date)))])
            ->actions([Tables\Actions\ViewAction::make()])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageInventoryMovements::route('/')];
    }
}
