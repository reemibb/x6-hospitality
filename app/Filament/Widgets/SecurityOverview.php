<?php

namespace App\Filament\Widgets;

use App\Models\LoginAttempt;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SecurityOverview extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $now = Carbon::now();
        
        $suspiciousIPs = LoginAttempt::failed()
            ->recent(24)
            ->select('ip_address')
            ->groupBy('ip_address')
            ->havingRaw('COUNT(*) > 5')
            ->count();
            
        $multipleFailedEmails = LoginAttempt::failed()
            ->recent(24)
            ->select('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 3')
            ->count();
            
        $activeUsers = User::whereHas('loginAttempts', function ($query) {
            $query->successful()->recent(24);
        })->count();
        
        $brute_force_attempts = LoginAttempt::failed()
            ->recent(1) 
            ->count();
        
        return [
            Stat::make('Active Users (24h)', $activeUsers)
                ->description('Users who logged in successfully')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            
            Stat::make('Suspicious IPs', $suspiciousIPs)
                ->description('IPs with 5+ failed attempts')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($suspiciousIPs > 0 ? 'danger' : 'success'),
            
            Stat::make('Targeted Emails', $multipleFailedEmails)
                ->description('Emails with 3+ failed attempts')
                ->descriptionIcon('heroicon-m-envelope')
                ->color($multipleFailedEmails > 0 ? 'warning' : 'success'),
            
            Stat::make('Brute Force (1h)', $brute_force_attempts)
                ->description($brute_force_attempts > 20 ? 'Possible attack!' : 'Normal activity')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($brute_force_attempts > 20 ? 'danger' : ($brute_force_attempts > 10 ? 'warning' : 'success')),
        ];
    }
}
