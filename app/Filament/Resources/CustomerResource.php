<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\BaseResource;
use App\Filament\Forms\BranchField;
use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Router;
use App\Services\MikrotikProvisioningService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends BaseResource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Manajemen Pelanggan';

    protected static ?string $navigationLabel = 'Pelanggan';

    protected static ?string $modelLabel = 'pelanggan';

    protected static ?string $pluralModelLabel = 'pelanggan';

    protected static string $feature = 'customers';

    public static function form(Form $form): Form
    {
        return $form->schema([
            BranchField::make(),
            Forms\Components\TextInput::make('full_name')->label('Nama lengkap')->required()->maxLength(160),
            Forms\Components\TextInput::make('phone')->label('Nomor telepon/WhatsApp')->tel()->maxLength(30),
            Forms\Components\TextInput::make('email')->label('Email')->email()->maxLength(180),
            Forms\Components\Select::make('service_type')->label('Jenis layanan')->options(['pppoe' => 'PPPoE', 'static_ip' => 'IP Static', 'hotspot' => 'Hotspot'])->required()->default('pppoe')->live(),
            Forms\Components\Select::make('plan_id')->label('Paket')->options(fn (): array => Plan::query()->where('active', true)->when(auth()->user()?->isBranchScoped(), fn (Builder $query) => $query->where(fn (Builder $branchQuery) => $branchQuery->whereNull('branch_id')->orWhere('branch_id', auth()->user()->branch_id)))->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
            Forms\Components\Select::make('connection_mode')->label('Aksi PPPoE')->options(['create' => 'Buat akun di router', 'attach' => 'Gunakan akun yang sudah ada'])->default('create')->visible(fn (Forms\Get $get): bool => $get('service_type') === 'pppoe'),
            Forms\Components\Select::make('mikrotik_router_id')->label('Router MikroTik')->options(fn (): array => Router::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all())->searchable()->visible(fn (Forms\Get $get): bool => in_array($get('service_type'), ['pppoe', 'static_ip'], true)),
            Forms\Components\TextInput::make('mikrotik_username')->label('Username PPPoE')->maxLength(100)->required(fn (Forms\Get $get): bool => $get('service_type') === 'pppoe'),
            Forms\Components\TextInput::make('mikrotik_password')->label('Password PPPoE')->password()->revealable()->formatStateUsing(fn (): null => null)->dehydrated(fn (?string $state): bool => filled($state))->required(fn (Forms\Get $get, string $operation): bool => $get('service_type') === 'pppoe' && $operation === 'create' && $get('connection_mode') === 'create')->helperText('Kosongkan saat mengedit agar password router yang tersimpan tetap dipakai.')->visible(fn (Forms\Get $get): bool => $get('service_type') === 'pppoe'),
            Forms\Components\TextInput::make('static_ip')->label('Alamat IP static')->ipv4()->visible(fn (Forms\Get $get): bool => $get('service_type') === 'static_ip'),
            Forms\Components\TextInput::make('due_day')->label('Tanggal jatuh tempo')->numeric()->minValue(1)->maxValue(31)->required()->default(20),
            Forms\Components\Toggle::make('auto_billing')->label('Tagihan otomatis')->default(true),
            Forms\Components\Select::make('status')->label('Status pelanggan')->options(['active' => 'Aktif', 'suspended' => 'Terisolir', 'terminated' => 'Berhenti'])->required()->default('active'),
            Forms\Components\DatePicker::make('installed_at')->label('Tanggal pemasangan'),
            Forms\Components\Textarea::make('address')->label('Alamat pemasangan')->columnSpanFull(),
            Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('customer_code')->label('Kode')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('full_name')->label('Pelanggan')->searchable()->sortable()->description(fn (Customer $record): ?string => $record->phone),
            Tables\Columns\TextColumn::make('branch.name')->label('Cabang'),
            Tables\Columns\TextColumn::make('service_type')->label('Layanan')->badge(),
            Tables\Columns\TextColumn::make('plan.name')->label('Paket')->placeholder('Tanpa paket'),
            Tables\Columns\TextColumn::make('mikrotik_username')->label('Username PPPoE')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('static_ip')->label('IP static')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('due_day')->label('Jatuh tempo')->suffix(' setiap bulan'),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => $state === 'active' ? 'success' : ($state === 'suspended' ? 'warning' : 'gray')),
        ])->filters([
            Tables\Filters\SelectFilter::make('service_type')->options(['pppoe' => 'PPPoE', 'static_ip' => 'IP Static', 'hotspot' => 'Hotspot']),
            Tables\Filters\SelectFilter::make('status')->options(['active' => 'Aktif', 'suspended' => 'Terisolir', 'terminated' => 'Berhenti']),
            Tables\Filters\SelectFilter::make('plan_id')->relationship('plan', 'name'),
        ])->actions([
            Tables\Actions\Action::make('syncPppoe')
                ->label('Sinkronkan MikroTik')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->visible(fn (Customer $record): bool => $record->service_type === 'pppoe')
                ->action(function (Customer $record): void {
                    try {
                        app(MikrotikProvisioningService::class)->syncPppoe($record);
                        Notification::make()->title('Akun PPPoE disinkronkan ke router')->success()->send();
                    } catch (\Throwable $exception) {
                        report($exception);
                        Notification::make()->title('Sinkronisasi router gagal')->body('Periksa koneksi router, kredensial, dan data PPPoE.')->danger()->send();
                    }
                }),
            Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make(),
        ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCustomers::route('/')];
    }
}
