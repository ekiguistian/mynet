<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\BaseResource;
use App\Filament\Forms\BranchField;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\UserRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends BaseResource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Organisasi & Pengguna';

    protected static ?string $navigationLabel = 'Pengguna & Peran';

    protected static string $feature = 'users';

    protected static bool $allowDelete = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama tampilan')->required()->maxLength(160),
            Forms\Components\TextInput::make('full_name')->label('Nama lengkap')->maxLength(160),
            Forms\Components\TextInput::make('username')->label('Username')->maxLength(80)->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('email')->label('Email')->email()->unique(ignoreRecord: true)->maxLength(180),
            Forms\Components\TextInput::make('phone')->label('Nomor WhatsApp')->tel()->maxLength(30),
            Forms\Components\Select::make('role')->label('Jabatan')->options(fn (): array => collect(UserRole::options())->reject(fn ($label, $role): bool => $role === 'administrator' && ! auth()->user()?->isGlobalAdministrator())->all())->required()->default('admin'),
            BranchField::make(required: false),
            Forms\Components\TextInput::make('password')->label('Kata sandi sementara')->password()->revealable()->minLength(12)->required(fn (string $operation): bool => $operation === 'create')->dehydrated(fn (?string $state): bool => filled($state)),
            Forms\Components\Toggle::make('force_password_change')->label('Wajib ganti kata sandi saat masuk berikutnya')->default(true),
            Forms\Components\Toggle::make('active')->label('Akun aktif')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Pengguna')->searchable()->sortable()->description(fn (User $record): ?string => $record->email),
            Tables\Columns\TextColumn::make('username')->label('Username')->searchable(),
            Tables\Columns\TextColumn::make('role')->label('Peran')->badge(),
            Tables\Columns\TextColumn::make('branch.name')->label('Cabang')->placeholder('Semua cabang'),
            Tables\Columns\IconColumn::make('active')->label('Aktif')->boolean(),
            Tables\Columns\TextColumn::make('last_login_at')->label('Terakhir masuk')->since()->placeholder('Belum pernah'),
        ])->filters([Tables\Filters\SelectFilter::make('role')->options(UserRole::options()), Tables\Filters\TernaryFilter::make('active')->label('Status akun')])
            ->actions([Tables\Actions\EditAction::make()])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageUsers::route('/')];
    }
}
