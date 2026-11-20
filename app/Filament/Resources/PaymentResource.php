<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\BaseResource;
use App\Filament\Forms\BranchField;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentGatewayService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends BaseResource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Keuangan & Tagihan';

    protected static ?string $navigationLabel = 'Pembayaran';

    protected static string $feature = 'payments';

    protected static bool $allowDelete = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            BranchField::make(),
            Forms\Components\Select::make('invoice_id')->label('Tagihan')->options(fn (): array => Invoice::query()->where('status', '!=', 'void')->orderByDesc('due_date')->get()->mapWithKeys(fn (Invoice $invoice): array => [$invoice->id => $invoice->invoice_no.' — '.$invoice->customer?->full_name.' — Rp '.number_format((float) $invoice->total, 0, ',', '.')])->all())->searchable()->preload()->required()->live()->afterStateUpdated(function (Forms\Set $set, $state): void {
                $invoice = Invoice::query()->find($state);
                $set('customer_id', $invoice?->customer_id);
                $set('branch_id', $invoice?->branch_id);
                $set('amount', $invoice?->total);
            }),
            Forms\Components\Select::make('customer_id')->label('Pelanggan')->options(fn (): array => Customer::query()->orderBy('full_name')->pluck('full_name', 'id')->all())->searchable()->required()->disabled()->dehydrated(),
            Forms\Components\Select::make('provider')->label('Penyedia')->options(['manual' => 'Manual', 'midtrans' => 'Midtrans', 'xendit' => 'Xendit', 'tripay' => 'Tripay', 'other' => 'Lainnya'])->required()->default('manual'),
            Forms\Components\TextInput::make('payment_method')->label('Metode pembayaran')->maxLength(80),
            Forms\Components\TextInput::make('amount')->label('Jumlah diterima')->numeric()->prefix('Rp')->required()->minValue(1)->maxValue(fn (Forms\Get $get): ?float => (float) (Invoice::query()->whereKey($get('invoice_id'))->value('total') ?? 0)),
            Forms\Components\Select::make('status')->label('Status')->options(['pending' => 'Menunggu', 'paid' => 'Berhasil', 'failed' => 'Gagal', 'expired' => 'Kedaluwarsa'])->required()->default('pending'),
            Forms\Components\TextInput::make('external_reference')->label('Referensi penyedia')->maxLength(120),
            Forms\Components\DateTimePicker::make('paid_at')->label('Waktu pembayaran')->visible(fn (Forms\Get $get): bool => $get('status') === 'paid'),
            Forms\Components\Textarea::make('checkout_url')->label('URL pembayaran')->url()->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->label('Waktu')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('invoice.invoice_no')->label('Tagihan')->searchable(),
            Tables\Columns\TextColumn::make('customer.full_name')->label('Pelanggan')->searchable(),
            Tables\Columns\TextColumn::make('provider')->label('Penyedia')->badge(),
            Tables\Columns\TextColumn::make('payment_method')->label('Metode')->placeholder('—'),
            Tables\Columns\TextColumn::make('amount')->label('Jumlah')->money('IDR')->sortable(),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
            Tables\Columns\TextColumn::make('external_reference')->label('Referensi')->toggleable(isToggledHiddenByDefault: true),
        ])->filters([Tables\Filters\SelectFilter::make('status')->options(['pending' => 'Menunggu', 'paid' => 'Berhasil', 'failed' => 'Gagal', 'expired' => 'Kedaluwarsa']), Tables\Filters\SelectFilter::make('provider')->options(['manual' => 'Manual', 'midtrans' => 'Midtrans', 'xendit' => 'Xendit', 'tripay' => 'Tripay', 'other' => 'Lainnya'])])
            ->actions([
                Tables\Actions\Action::make('createCheckout')->label('Buat tautan bayar')->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn (Payment $record): bool => $record->status === 'pending' && in_array($record->provider, ['midtrans', 'xendit', 'tripay'], true))
                    ->action(function (Payment $record): void {
                        try {
                            $url = app(PaymentGatewayService::class)->createCheckout($record);
                            Notification::make()->title('Tautan pembayaran berhasil dibuat')->body($url)->success()->persistent()->send();
                        } catch (\Throwable $exception) {
                            report($exception);
                            Notification::make()->title('Tautan pembayaran gagal dibuat')->body('Periksa konfigurasi gateway dan data pelanggan.')->danger()->send();
                        }
                    }),
                Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePayments::route('/')];
    }
}
