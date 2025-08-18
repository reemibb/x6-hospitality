<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class BookingOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $now = Carbon::now()->startOfDay();
        
        return [
            Stat::make('Total Bookings', Booking::count())
                ->description('All time bookings')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
            
            Stat::make('Active Bookings', Booking::active()->count())
                ->description('Currently checked-in')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            
            Stat::make('Pending Bookings', Booking::pending()->count())
                ->description('Awaiting confirmation')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            
            Stat::make('This Month Revenue', function () {
                return '$' . number_format(
                    Booking::whereMonth('created_at', now()->month)
                           ->whereYear('created_at', now()->year)
                           ->where('status', '!=', 'cancelled')
                           ->sum('total_price'), 2
                );
            })
                ->description('Current month earnings')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
        ];
    }
}
