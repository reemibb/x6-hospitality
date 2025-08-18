<?php

namespace App\Filament\Widgets;

use App\Models\Availability;
use App\Models\Room;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class AvailabilityOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $now = Carbon::now()->startOfDay();
        
        return [
            Stat::make('Total Availabilities', Availability::count())
                ->description('All availability records')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
            
            Stat::make('Active Now', Availability::active()->count())
                ->description('Currently available rooms')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            
            Stat::make('Upcoming', Availability::upcoming()->count())
                ->description('Future availability periods')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            
            Stat::make('Total Rooms', Room::count())
                ->description('All rooms in system')
                ->descriptionIcon('heroicon-m-home')
                ->color('info'),
        ];
    }
}
