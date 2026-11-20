<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\BaseResource;
use App\Filament\Forms\BranchField;
use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use App\Models\User;
use App\UserRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class AttendanceResource extends BaseResource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Absensi Karyawan';

    protected static string $feature = 'attendance';

    public static function form(Form $form): Form
    {
        return $form->schema([
            BranchField::make(),
            Forms\Components\Select::make('user_id')->label('Karyawan')->options(fn (Forms\Get $get): array => User::query()->where('active', true)->whereIn('role', array_map(fn (UserRole $role): string => $role->value, UserRole::cases()))->when($get('branch_id'), fn ($query, $branchId) => $query->where('branch_id', $branchId))->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->required(),
            Forms\Components\DatePicker::make('work_date')->label('Tanggal kerja')->required()->default(now()),
            Forms\Components\DateTimePicker::make('check_in')->label('Jam masuk'),
            Forms\Components\DateTimePicker::make('check_out')->label('Jam pulang'),
            Forms\Components\Select::make('status')->label('Status')->options(['present' => 'Hadir', 'late' => 'Terlambat', 'leave' => 'Izin', 'sick' => 'Sakit', 'absent' => 'Tidak hadir'])->required()->default('present'),
            Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('work_date')->label('Tanggal')->date()->sortable(),
            Tables\Columns\TextColumn::make('user.name')->label('Karyawan')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('user.role')->label('Jabatan')->badge(),
            Tables\Columns\TextColumn::make('branch.name')->label('Cabang'),
            Tables\Columns\TextColumn::make('check_in')->label('Masuk')->dateTime(),
            Tables\Columns\TextColumn::make('check_out')->label('Pulang')->dateTime(),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
            Tables\Columns\TextColumn::make('check_in_latitude')->label('Koordinat masuk')->formatStateUsing(fn ($state, Attendance $record) => $state && $record->check_in_longitude ? $state.', '.$record->check_in_longitude : '—')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('check_in_photo_path')->label('Foto masuk')->formatStateUsing(fn ($state): string => filled($state) ? 'Lihat foto' : '—')->url(fn (Attendance $record): ?string => $record->check_in_photo_path ? route('admin.attendance-photo', [$record, 'check_in']) : null)->openUrlInNewTab()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('check_out_photo_path')->label('Foto pulang')->formatStateUsing(fn ($state): string => filled($state) ? 'Lihat foto' : '—')->url(fn (Attendance $record): ?string => $record->check_out_photo_path ? route('admin.attendance-photo', [$record, 'check_out']) : null)->openUrlInNewTab()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options(['present' => 'Hadir', 'late' => 'Terlambat', 'leave' => 'Izin', 'sick' => 'Sakit', 'absent' => 'Tidak hadir']),
            Tables\Filters\SelectFilter::make('user_id')->label('Karyawan')->options(fn (): array => User::query()->when(auth()->user()?->isBranchScoped(), fn ($query) => $query->where('branch_id', auth()->user()->branch_id))->orderBy('name')->pluck('name', 'id')->all())->searchable(),
            Tables\Filters\Filter::make('work_date')->form([Forms\Components\DatePicker::make('from')->label('Dari'), Forms\Components\DatePicker::make('until')->label('Sampai')])->query(fn ($query, array $data) => $query->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('work_date', '>=', $date))->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('work_date', '<=', $date))),
        ])->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()])->defaultSort('work_date', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageAttendances::route('/')];
    }
}
