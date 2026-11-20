<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\BaseResource;
use App\Filament\Forms\BranchField;
use App\Filament\Resources\WorkOrderResource\Pages;
use App\Models\Customer;
use App\Models\User;
use App\Models\WorkOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class WorkOrderResource extends BaseResource
{
    protected static ?string $model = WorkOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Pekerjaan Teknisi';

    protected static string $feature = 'work_orders';

    public static function form(Form $form): Form
    {
        return $form->schema([
            BranchField::make(),
            Forms\Components\Select::make('customer_id')->label('Pelanggan')->options(fn (): array => Customer::query()->orderBy('full_name')->get()->mapWithKeys(fn (Customer $customer): array => [$customer->id => $customer->customer_code.' — '.$customer->full_name])->all())->searchable()->preload(),
            Forms\Components\TextInput::make('title')->label('Judul pekerjaan')->required()->maxLength(160),
            Forms\Components\Select::make('technician_id')->label('Teknisi')->options(fn (Forms\Get $get): array => User::query()->where('active', true)->whereIn('role', ['technician', 'helper'])->when($get('branch_id'), fn ($query, $branchId) => $query->where('branch_id', $branchId))->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
            Forms\Components\Select::make('sales_user_id')->label('Sales terkait')->options(fn (Forms\Get $get): array => User::query()->where('active', true)->where('role', 'sales')->when($get('branch_id'), fn ($query, $branchId) => $query->where('branch_id', $branchId))->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
            Forms\Components\Select::make('status')->label('Status')->options(['open' => 'Terbuka', 'scheduled' => 'Terjadwal', 'in_progress' => 'Dikerjakan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'])->required()->default('open'),
            Forms\Components\DateTimePicker::make('scheduled_at')->label('Jadwal'),
            Forms\Components\DateTimePicker::make('completed_at')->label('Selesai pada'),
            Forms\Components\TextInput::make('billable_amount')->label('Nilai pekerjaan')->numeric()->prefix('Rp')->default(0)->required(),
            Forms\Components\Textarea::make('description')->label('Deskripsi')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('work_order_no')->label('Nomor pekerjaan')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('title')->label('Pekerjaan')->searchable(),
            Tables\Columns\TextColumn::make('branch.name')->label('Cabang'),
            Tables\Columns\TextColumn::make('customer.full_name')->label('Pelanggan'),
            Tables\Columns\TextColumn::make('technician.name')->label('Teknisi')->placeholder('Belum ditugaskan'),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
            Tables\Columns\TextColumn::make('scheduled_at')->label('Jadwal')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('billable_amount')->label('Nilai')->money('IDR'),
        ])->filters([Tables\Filters\SelectFilter::make('status')->options(['open' => 'Terbuka', 'scheduled' => 'Terjadwal', 'in_progress' => 'Dikerjakan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan']), Tables\Filters\SelectFilter::make('technician_id')->label('Teknisi')->options(fn (): array => User::query()->whereIn('role', ['technician', 'helper'])->when(auth()->user()?->isBranchScoped(), fn ($query) => $query->where('branch_id', auth()->user()->branch_id))->orderBy('name')->pluck('name', 'id')->all())->searchable()])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()])->defaultSort('scheduled_at');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageWorkOrders::route('/')];
    }
}
