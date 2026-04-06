<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\File;
use UnitEnum;

class ApsLogs extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | UnitEnum | null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'APS Logs';

    protected static ?string $title = 'Amazon Payment Services Logs';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.aps-logs';

    public string $logContent = '';

    public int $lines = 100;

    public function mount(): void
    {
        $this->loadLogs();
    }

    public static function canAccess(): bool
    {
        /** @var Authenticatable|null $user */
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole(['super_admin', 'owner', 'admin']);
        }

        return false;
    }

    public function loadLogs(): void
    {
        $logPath = storage_path('logs/aps.log');

        if (! File::exists($logPath)) {
            $this->logContent = 'No APS logs found yet.';
            return;
        }

        $content = File::get($logPath);
        $allLines = explode("\n", $content);
        $lastLines = array_slice($allLines, -$this->lines);
        $this->logContent = implode("\n", array_reverse($lastLines));
    }

    public function refresh(): void
    {
        $this->loadLogs();
    }

    public function clearLogs(): void
    {
        $logPath = storage_path('logs/aps.log');

        if (File::exists($logPath)) {
            File::put($logPath, '');
        }

        $this->logContent = 'Logs cleared.';
    }
}
