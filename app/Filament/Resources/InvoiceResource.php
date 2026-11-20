<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\BaseResource;
use App\Filament\Forms\BranchField;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Customer;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends BaseResource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationGroup = 'Keuangan & Tagihan';

    protected static ?string $navigationLabel = 'Tagihan';

    protected static ?string $modelLabel = 'tagihan';

    protected static ?string $pluralModelLabel = 'tagihan';

    protected static string $feature = 'invoices';

    protected static bool $allowDelete = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            BranchField::make(),
            Forms\Components\Select::make('customer_id')->label('Pelanggan')->options(fn (): array => Customer::query()->orderBy('full_name')->pluck('full_name', 'id')->all())->searchable()->preload()->required()->live()->afterStateUpdated(function (Forms\Set $set, $state): void {
                $set('branch_id', Customer::query()->whereKey($state)->value('branch_id'));
            }),
            Forms\Components\TextInput::make('period')->label('Periode tagihan')->placeholder('YYYY-MM')->mask('9999-99')->required()->maxLength(7),
            Forms\Components\TextInput::make('amount')->label('Jumlah')->numeric()->prefix('Rp')->required()->minValue(0),
            Forms\Components\TextInput::make('discount')->label('Diskon')->numeric()->prefix('Rp')->default(0)->minValue(0),
            Forms\Components\DatePicker::make('due_date')->label('Jatuh tempo')->required(),
            Forms\Components\Select::make('status')->label('Status')->options(['unpaid' => 'Belum dibayar', 'paid' => 'Lunas', 'void' => 'Dibatalkan'])->required()->default('unpaid')->disabled(fn (?Invoice $record): bool => $record?->status === 'paid'),
            Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('invoice_no')->label('Nomor tagihan')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('customer.full_name')->label('Pelanggan')->searchable(),
            Tables\Columns\TextColumn::make('period')->label('Periode')->sortable(),
            Tables\Columns\TextColumn::make('total')->label('Total')->money('IDR')->sortable(),
            Tables\Columns\TextColumn::make('due_date')->label('Jatuh tempo')->date()->sortable(),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => $state === 'paid' ? 'success' : ($state === 'unpaid' ? 'warning' : 'gray')),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options(['unpaid' => 'Belum dibayar', 'paid' => 'Lunas', 'void' => 'Dibatalkan']),
            Tables\Filters\Filter::make('due_date')->form([Forms\Components\DatePicker::make('from')->label('Dari'), Forms\Components\DatePicker::make('until')->label('Sampai')])->query(fn ($query, array $data) => $query->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('due_date', '>=', $date))->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('due_date', '<=', $date))),
        ])->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])])
            ->defaultSort('due_date', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageInvoices::route('/')];
    }
}
