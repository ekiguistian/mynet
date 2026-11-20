<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ActivityLogs extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Pengaturan Sistem';

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.activity-logs';

    public string $eventFilter = '';

    public string $userFilter = '';

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User && auth()->user()->isGlobalAdministrator();
    }

    public function logs(): LengthAwarePaginator
    {
        return ActivityLog::query()->with(['user', 'branch', 'subject'])
            ->when($this->eventFilter !== '', fn ($query) => $query->where('event', $this->eventFilter))
            ->when($this->userFilter !== '', fn ($query) => $query->whereHas('user', fn ($user) => $user->where('name', 'like', '%'.$this->userFilter.'%')))
            ->latest()->paginate(50);
    }

    public function eventOptions(): array
    {
        return ActivityLog::query()->distinct()->orderBy('event')->pluck('event', 'event')->all();
    }
}
