<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

class MyAttendance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Absensi Saya';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.my-attendance';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->canAccessFeature('attendance');
    }

    public function getHeaderActions(): array
    {
        $attendance = $this->todayAttendance();

        if (! $attendance?->check_in) {
            return [$this->attendanceAction('checkIn', 'Absen masuk', 'check_in')];
        }

        if (! $attendance->check_out) {
            return [$this->attendanceAction('checkOut', 'Absen pulang', 'check_out')];
        }

        return [];
    }

    public function todayAttendance(): ?Attendance
    {
        return Attendance::query()->where('user_id', auth()->id())->whereDate('work_date', today())->first();
    }

    public function recentAttendance()
    {
        return Attendance::query()->where('user_id', auth()->id())->with('branch')->latest('work_date')->limit(14)->get();
    }

    private function attendanceAction(string $name, string $label, string $direction): Action
    {
        return Action::make($name)->label($label)->icon('heroicon-o-map-pin')->color($direction === 'check_in' ? 'success' : 'primary')
            ->modalHeading($label)
            ->modalDescription('Pastikan GPS dan foto diambil dari lokasi cabang Anda.')
            ->form([
                TextInput::make('latitude')->label('Latitude GPS')->numeric()->required()->minValue(-90)->maxValue(90),
                TextInput::make('longitude')->label('Longitude GPS')->numeric()->required()->minValue(-180)->maxValue(180),
                FileUpload::make('photo_path')->label('Foto absensi')->image()->disk('local')->directory('attendance')->required()->maxSize(5120),
                Textarea::make('notes')->label('Catatan')->maxLength(1000),
            ])->action(function (array $data) use ($direction): void {
                /** @var User $user */
                $user = auth()->user();
                $branch = $user->branch_id ? Branch::query()->find($user->branch_id) : null;

                if (! $branch || $branch->latitude === null || $branch->longitude === null) {
                    throw ValidationException::withMessages(['latitude' => 'Cabang belum memiliki koordinat absensi. Hubungi administrator.']);
                }

                if (! $this->isWithinBranch((float) $data['latitude'], (float) $data['longitude'], (float) $branch->latitude, (float) $branch->longitude, (int) $branch->attendance_radius_m)) {
                    throw ValidationException::withMessages(['latitude' => 'Lokasi GPS berada di luar radius absensi cabang.']);
                }

                $attendance = Attendance::query()->firstOrNew(['user_id' => $user->getKey(), 'work_date' => today()->toDateString()]);
                $field = $direction;

                if ($attendance->exists && $attendance->{$field}) {
                    throw ValidationException::withMessages(['work_date' => 'Absensi ini sudah tercatat.']);
                }

                $attendance->branch_id = $branch->getKey();
                $attendance->status = $attendance->status ?: 'present';
                $attendance->{$field} = now();
                $attendance->{$direction.'_latitude'} = $data['latitude'];
                $attendance->{$direction.'_longitude'} = $data['longitude'];
                $attendance->{$direction.'_photo_path'} = $data['photo_path'];
                $attendance->{$direction.'_ip'} = request()->ip();
                $attendance->notes = $data['notes'] ?? $attendance->notes;
                $attendance->save();

                Notification::make()->title($direction === 'check_in' ? 'Absensi masuk tersimpan' : 'Absensi pulang tersimpan')->success()->send();
            });
    }

    private function isWithinBranch(float $latitude, float $longitude, float $branchLatitude, float $branchLongitude, int $radius): bool
    {
        $earthRadius = 6371000;
        $latitudeDelta = deg2rad($latitude - $branchLatitude);
        $longitudeDelta = deg2rad($longitude - $branchLongitude);
        $a = sin($latitudeDelta / 2) ** 2 + cos(deg2rad($branchLatitude)) * cos(deg2rad($latitude)) * sin($longitudeDelta / 2) ** 2;

        return 2 * $earthRadius * atan2(sqrt($a), sqrt(1 - $a)) <= $radius;
    }
}
