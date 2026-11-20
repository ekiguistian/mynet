<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\BaseResource;
use App\Filament\Forms\BranchField;
use App\Filament\Resources\MaintenanceTicketResource\Pages;
use App\Models\Customer;
use App\Models\MaintenanceTicket;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class MaintenanceTicketResource extends BaseResource
{
    protected static ?string $model = MaintenanceTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Tiket Gangguan';

    protected static string $feature = 'tickets';

    public static function form(Form $form): Form
    {
        return $form->schema([
            BranchField::make(),
            Forms\Components\Select::make('customer_id')->label('Pelanggan')->options(fn (): array => Customer::query()->orderBy('full_name')->get()->mapWithKeys(fn (Customer $customer): array => [$customer->id => $customer->customer_code.' — '.$customer->full_name])->all())->searchable()->preload(),
            Forms\Components\TextInput::make('title')->label('Judul')->required()->maxLength(160),
            Forms\Components\Select::make('category')->label('Kategori')->options(['internet' => 'Internet', 'installation' => 'Instalasi', 'billing' => 'Tagihan', 'device' => 'Perangkat', 'other' => 'Lainnya'])->required(),
            Forms\Components\Select::make('priority')->label('Prioritas')->options(['low' => 'Rendah', 'normal' => 'Normal', 'high' => 'Tinggi', 'urgent' => 'Darurat'])->required()->default('normal'),
            Forms\Components\Select::make('status')->label('Status')->options(['open' => 'Baru', 'assigned' => 'Ditugaskan', 'in_progress' => 'Dikerjakan', 'resolved' => 'Selesai', 'closed' => 'Ditutup'])->required()->default('open'),
            Forms\Components\Select::make('assigned_to')->label('Teknisi')->options(fn (Forms\Get $get): array => User::query()->where('active', true)->whereIn('role', ['technician', 'helper'])->when($get('branch_id'), fn ($query, $branchId) => $query->where('branch_id', $branchId))->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
            Forms\Components\DateTimePicker::make('scheduled_at')->label('Jadwal penanganan'),
            Forms\Components\Textarea::make('description')->label('Deskripsi gangguan')->required()->columnSpanFull(),
            Forms\Components\Textarea::make('resolution')->label('Penyelesaian')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('ticket_no')->label('Nomor tiket')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('title')->label('Gangguan')->searchable()->limit(50),
            Tables\Columns\TextColumn::make('customer.full_name')->label('Pelanggan')->searchable(),
            Tables\Columns\TextColumn::make('branch.name')->label('Cabang'),
            Tables\Columns\TextColumn::make('assignee.name')->label('Teknisi')->placeholder('Belum ditugaskan'),
            Tables\Columns\TextColumn::make('priority')->label('Prioritas')->badge(),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
            Tables\Columns\TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable(),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options(['open' => 'Baru', 'assigned' => 'Ditugaskan', 'in_progress' => 'Dikerjakan', 'resolved' => 'Selesai', 'closed' => 'Ditutup']),
            Tables\Filters\SelectFilter::make('priority')->options(['low' => 'Rendah', 'normal' => 'Normal', 'high' => 'Tinggi', 'urgent' => 'Darurat']),
            Tables\Filters\SelectFilter::make('assigned_to')->label('Teknisi')->options(fn (): array => User::query()->whereIn('role', ['technician', 'helper'])->when(auth()->user()?->isBranchScoped(), fn ($query) => $query->where('branch_id', auth()->user()->branch_id))->orderBy('name')->pluck('name', 'id')->all())->searchable(),
        ])->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageMaintenanceTickets::route('/')];
    }
}
