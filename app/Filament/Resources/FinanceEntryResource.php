<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\BaseResource;
use App\Filament\Forms\BranchField;
use App\Filament\Resources\FinanceEntryResource\Pages;
use App\Models\FinanceCategory;
use App\Models\FinanceEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FinanceEntryResource extends BaseResource
{
    protected static ?string $model = FinanceEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Keuangan & Tagihan';

    protected static ?string $navigationLabel = 'Arus Kas';

    protected static ?string $modelLabel = 'transaksi keuangan';

    protected static ?string $pluralModelLabel = 'transaksi keuangan';

    protected static string $feature = 'finance';

    public static function form(Form $form): Form
    {
        return $form->schema([
            BranchField::make(),
            Forms\Components\Select::make('entry_type')->label('Jenis transaksi')->options(['income' => 'Pendapatan', 'expense' => 'Pengeluaran'])->required()->default('income')->live(),
            Forms\Components\Select::make('finance_category_id')->label('Kategori')->options(fn (Forms\Get $get): array => FinanceCategory::query()->where('active', true)->when(auth()->user()?->isBranchScoped(), fn (Builder $query) => $query->where(fn (Builder $branchQuery) => $branchQuery->whereNull('branch_id')->orWhere('branch_id', auth()->user()->branch_id)))->where(fn (Builder $query) => $query->where('type', 'both')->orWhere('type', $get('entry_type') ?: 'income'))->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->required(),
            Forms\Components\TextInput::make('amount')->label('Jumlah')->numeric()->prefix('Rp')->required()->minValue(0.01),
            Forms\Components\DatePicker::make('transaction_date')->label('Tanggal transaksi')->required()->default(now()),
            Forms\Components\TextInput::make('reference')->label('Nomor referensi')->maxLength(100),
            Forms\Components\Textarea::make('description')->label('Keterangan')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('transaction_date')->label('Tanggal')->date()->sortable(),
            Tables\Columns\TextColumn::make('entry_type')->label('Jenis')->badge()->formatStateUsing(fn (string $state): string => $state === 'income' ? 'Pendapatan' : 'Pengeluaran')->color(fn (string $state): string => $state === 'income' ? 'success' : 'danger'),
            Tables\Columns\TextColumn::make('categoryRecord.name')->label('Kategori')->placeholder(fn (FinanceEntry $record): string => $record->category),
            Tables\Columns\TextColumn::make('branch.name')->label('Cabang'),
            Tables\Columns\TextColumn::make('amount')->label('Jumlah')->money('IDR')->sortable(),
            Tables\Columns\TextColumn::make('description')->label('Keterangan')->limit(50),
            Tables\Columns\TextColumn::make('user.name')->label('Dicatat oleh'),
        ])->filters([
            Tables\Filters\SelectFilter::make('entry_type')->options(['income' => 'Pendapatan', 'expense' => 'Pengeluaran']),
            Tables\Filters\SelectFilter::make('finance_category_id')->relationship('categoryRecord', 'name'),
            Tables\Filters\Filter::make('transaction_date')->form([Forms\Components\DatePicker::make('from')->label('Dari'), Forms\Components\DatePicker::make('until')->label('Sampai')])->query(fn ($query, array $data) => $query->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('transaction_date', '>=', $date))->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('transaction_date', '<=', $date))),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])])
            ->defaultSort('transaction_date', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageFinanceEntries::route('/')];
    }
}
