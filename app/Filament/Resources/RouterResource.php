<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\BaseResource;
use App\Filament\Forms\BranchField;
use App\Filament\Resources\RouterResource\Pages;
use App\Models\Router;
use App\Services\MikrotikProvisioningService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;

class RouterResource extends BaseResource
{
    protected static ?string $model = Router::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationGroup = 'Jaringan & MikroTik';

    protected static ?string $navigationLabel = 'Router MikroTik';

    protected static string $feature = 'routers';

    public static function form(Form $form): Form
    {
        return $form->schema([
            BranchField::make(),
            Forms\Components\TextInput::make('name')->label('Nama router')->required()->maxLength(120),
            Forms\Components\TextInput::make('host')->label('Alamat host')->maxLength(255)->helperText('Kosongkan untuk memakai host bawaan di Pengaturan Integrasi. Host dan kredensial disimpan terenkripsi.'),
            Forms\Components\TextInput::make('api_port')->label('Port API')->numeric()->helperText('Default 8728 atau 8729 bila TLS aktif.'),
            Forms\Components\TextInput::make('username')->label('Username API')->maxLength(255),
            Forms\Components\TextInput::make('password')->label('Password API')->password()->revealable()->formatStateUsing(fn (): null => null)->dehydrated(fn (?string $state): bool => filled($state))->helperText('Kosongkan untuk memakai kredensial bawaan di Pengaturan Integrasi.'),
            Forms\Components\Toggle::make('api_ssl')->label('Gunakan TLS')->default(false),
            Forms\Components\Toggle::make('verify_ssl')->label('Verifikasi sertifikat TLS')->default(true),
            Forms\Components\Toggle::make('active')->label('Aktif')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Router')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('branch.name')->label('Cabang'),
            Tables\Columns\IconColumn::make('api_ssl')->label('TLS')->boolean(),
            Tables\Columns\TextColumn::make('customers_count')->counts('customers')->label('Pelanggan'),
            Tables\Columns\IconColumn::make('active')->label('Aktif')->boolean(),
            Tables\Columns\TextColumn::make('updated_at')->label('Diubah')->since(),
        ])->actions([
            Tables\Actions\Action::make('testConnection')->label('Tes koneksi')->icon('heroicon-o-signal')->action(function (Router $record): void {
                try {
                    $version = app(MikrotikProvisioningService::class)->testConnection($record);
                    Notification::make()->title('Koneksi MikroTik berhasil')->body('RouterOS '.$version)->success()->send();
                } catch (\Throwable $exception) {
                    report($exception);
                    Notification::make()->title('Koneksi MikroTik gagal')->body('Periksa host, port, kredensial, dan izin API.')->danger()->send();
                }
            }),
            Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make(),
        ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageRouters::route('/')];
    }
}
