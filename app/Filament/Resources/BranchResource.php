<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\BaseResource;
use App\Filament\Resources\BranchResource\Pages;
use App\Models\Branch;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BranchResource extends BaseResource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Organisasi & Pengguna';

    protected static ?string $modelLabel = 'cabang';

    protected static ?string $pluralModelLabel = 'cabang';

    protected static ?string $navigationLabel = 'Cabang';

    protected static string $feature = 'branches';

    protected static bool $allowDelete = false;

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return parent::canCreate() && $user instanceof User && ! $user->isBranchScoped();
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return parent::canEdit($record) && $user instanceof User && ! $user->isBranchScoped();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nama cabang')
                ->required()
                ->maxLength(120),
            TextInput::make('code')
                ->label('Kode cabang')
                ->maxLength(30)
                ->unique(ignoreRecord: true),
            TextInput::make('phone')
                ->label('Nomor telepon')
                ->tel()
                ->maxLength(30),
            Select::make('status')
                ->label('Status')
                ->options(['active' => 'Aktif', 'inactive' => 'Nonaktif'])
                ->default('active')
                ->required(),
            Textarea::make('address')
                ->label('Alamat')
                ->columnSpanFull(),
            TextInput::make('latitude')
                ->label('Latitude absensi')
                ->numeric()
                ->minValue(-90)
                ->maxValue(90),
            TextInput::make('longitude')
                ->label('Longitude absensi')
                ->numeric()
                ->minValue(-180)
                ->maxValue(180),
            TextInput::make('attendance_radius_m')
                ->label('Radius absensi (meter)')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->maxValue(10000)
                ->default(250)
                ->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Cabang')->searchable()->sortable(),
                TextColumn::make('code')->label('Kode')->searchable(),
                TextColumn::make('phone')->label('Telepon'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray'),
                TextColumn::make('attendance_radius_m')->label('Radius absensi')->suffix(' m'),
            ])
            ->defaultSort('name')
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
