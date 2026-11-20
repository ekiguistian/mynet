<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\BaseResource;
use App\Filament\Forms\BranchField;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends BaseResource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Inventaris';

    protected static ?string $navigationLabel = 'Data Barang';

    protected static string $feature = 'inventory';

    public static function form(Form $form): Form
    {
        return $form->schema([
            BranchField::make(required: false, includeShared: true),
            Forms\Components\TextInput::make('sku')->label('SKU')->required()->maxLength(60),
            Forms\Components\TextInput::make('name')->label('Nama barang')->required()->maxLength(160),
            Forms\Components\Select::make('category')->label('Kategori')->options(['router' => 'Router', 'onu' => 'ONT/ONU', 'cable' => 'Kabel', 'accessory' => 'Aksesori', 'other' => 'Lainnya'])->required()->default('other'),
            Forms\Components\TextInput::make('unit')->label('Satuan')->required()->default('pcs')->maxLength(30),
            Forms\Components\TextInput::make('stock_qty')->label('Stok saat ini')->numeric()->disabled()->dehydrated(false)->default(0),
            Forms\Components\TextInput::make('minimum_stock')->label('Batas stok minimum')->numeric()->default(0)->minValue(0)->required(),
            Forms\Components\TextInput::make('cost_price')->label('Harga modal')->numeric()->prefix('Rp')->default(0)->required(),
            Forms\Components\TextInput::make('sale_price')->label('Harga jual')->numeric()->prefix('Rp')->default(0)->required(),
            Forms\Components\Toggle::make('active')->label('Aktif')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('sku')->label('SKU')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('name')->label('Barang')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('branch.name')->label('Cabang')->placeholder('Semua cabang'),
            Tables\Columns\TextColumn::make('category')->label('Kategori')->badge(),
            Tables\Columns\TextColumn::make('stock_qty')->label('Stok')->numeric(decimalPlaces: 2)->sortable()->color(fn (Product $record): string => (float) $record->stock_qty <= (float) $record->minimum_stock ? 'danger' : 'success'),
            Tables\Columns\TextColumn::make('unit')->label('Satuan'),
            Tables\Columns\TextColumn::make('cost_price')->label('Modal')->money('IDR')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\IconColumn::make('active')->label('Aktif')->boolean(),
        ])->filters([Tables\Filters\SelectFilter::make('category')->options(['router' => 'Router', 'onu' => 'ONT/ONU', 'cable' => 'Kabel', 'accessory' => 'Aksesori', 'other' => 'Lainnya']), Tables\Filters\TernaryFilter::make('active')->label('Status')])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->visible(fn (Product $record): bool => ! $record->movements()->exists())])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageProducts::route('/')];
    }
}
