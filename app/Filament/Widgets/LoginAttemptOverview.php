<?php

namespace App\Filament\Widgets;

use App\Models\LoginAttempt;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class LoginAttemptOverview extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $now = Carbon::now();
        
        $recentTotal = LoginAttempt::recent(24)->count();
        $recentSuccessful = LoginAttempt::successful()->recent(24)->count();
        $recentFailed = LoginAttempt::failed()->recent(24)->count();
        $recentUniqueIPs = LoginAttempt::recent(24)->distinct('ip_address')->count();
        
        $successRate = $recentTotal > 0 ? round(($recentSuccessful / $recentTotal) * 100, 1) : 0;
        
        return [
            Stat::make('Total Attempts (24h)', $recentTotal)
                ->description('Login attempts in last 24 hours')
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary')
                ->chart([
                    LoginAttempt::whereDate('attempted_at', $now->copy()->subDays(6))->count(),
                    LoginAttempt::whereDate('attempted_at', $now->copy()->subDays(5))->count(),
                    LoginAttempt::whereDate('attempted_at', $now->copy()->subDays(4))->count(),
                    LoginAttempt::whereDate('attempted_at', $now->copy()->subDays(3))->count(),
                    LoginAttempt::whereDate('attempted_at', $now->copy()->subDays(2))->count(),
                    LoginAttempt::whereDate('attempted_at', $now->copy()->subDays(1))->count(),
                    LoginAttempt::whereDate('attempted_at', $now)->count(),
                ]),
            
            Stat::make('Successful Logins (24h)', $recentSuccessful)
                ->description("Success rate: {$successRate}%")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([
                    LoginAttempt::successful()->whereDate('attempted_at', $now->copy()->subDays(6))->count(),
                    LoginAttempt::successful()->whereDate('attempted_at', $now->copy()->subDays(5))->count(),
                    LoginAttempt::successful()->whereDate('attempted_at', $now->copy()->subDays(4))->count(),
                    LoginAttempt::successful()->whereDate('attempted_at', $now->copy()->subDays(3))->count(),
                    LoginAttempt::successful()->whereDate('attempted_at', $now->copy()->subDays(2))->count(),
                    LoginAttempt::successful()->whereDate('attempted_at', $now->copy()->subDays(1))->count(),
                    LoginAttempt::successful()->whereDate('attempted_at', $now)->count(),
                ]),
            
            Stat::make('Failed Attempts (24h)', $recentFailed)
                ->description($recentFailed > 10 ? 'High failure rate - investigate' : 'Normal failure rate')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($recentFailed > 10 ? 'danger' : ($recentFailed > 5 ? 'warning' : 'info'))
                ->chart([
                    LoginAttempt::failed()->whereDate('attempted_at', $now->copy()->subDays(6))->count(),
                    LoginAttempt::failed()->whereDate('attempted_at', $now->copy()->subDays(5))->count(),
                    LoginAttempt::failed()->whereDate('attempted_at', $now->copy()->subDays(4))->count(),
                    LoginAttempt::failed()->whereDate('attempted_at', $now->copy()->subDays(3))->count(),
                    LoginAttempt::failed()->whereDate('attempted_at', $now->copy()->subDays(2))->count(),
                    LoginAttempt::failed()->whereDate('attempted_at', $now->copy()->subDays(1))->count(),
                    LoginAttempt::failed()->whereDate('attempted_at', $now)->count(),
                ]),
            
            Stat::make('Unique IPs (24h)', $recentUniqueIPs)
                ->description('Different IP addresses')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('warning'),
        ];
    }
}
