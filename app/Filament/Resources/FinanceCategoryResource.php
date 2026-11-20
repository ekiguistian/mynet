<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\BaseResource;
use App\Filament\Forms\BranchField;
use App\Filament\Resources\FinanceCategoryResource\Pages;
use App\Models\FinanceCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class FinanceCategoryResource extends BaseResource
{
    protected static ?string $model = FinanceCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Keuangan & Tagihan';

    protected static ?string $navigationLabel = 'Kategori Keuangan';

    protected static string $feature = 'finance';

    public static function form(Form $form): Form
    {
        return $form->schema([
            BranchField::make(required: false, includeShared: true),
            Forms\Components\TextInput::make('name')->label('Nama kategori')->required()->maxLength(100),
            Forms\Components\Select::make('type')->label('Jenis')->options(['income' => 'Pendapatan', 'expense' => 'Pengeluaran', 'both' => 'Keduanya'])->required()->default('both'),
            Forms\Components\Toggle::make('active')->label('Aktif')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Kategori')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('branch.name')->label('Cabang')->placeholder('Semua cabang'),
            Tables\Columns\TextColumn::make('type')->label('Jenis')->badge()->formatStateUsing(fn (string $state): string => ['income' => 'Pendapatan', 'expense' => 'Pengeluaran', 'both' => 'Keduanya'][$state] ?? $state),
            Tables\Columns\IconColumn::make('active')->label('Aktif')->boolean(),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageFinanceCategories::route('/')];
    }
}
