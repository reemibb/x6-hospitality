<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentOverview extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $now = Carbon::now();
        
        $todayPayments = Payment::whereDate('paid_at', $now->toDateString())->get();
        $todayRevenue = $todayPayments->sum(function ($payment) {
            return $payment->amount - ($payment->refunded_amount ?? 0);
        });
        
        $todayPaymentsCount = $todayPayments->count();
        
        $monthlyPayments = Payment::whereYear('paid_at', $now->year)
            ->whereMonth('paid_at', $now->month)
            ->get();
            
        $monthlyRevenue = $monthlyPayments->sum(function ($payment) {
            return $payment->amount - ($payment->refunded_amount ?? 0);
        });
        
        $pendingPayments = Payment::pending()->count();
        $overduePayments = Payment::where('payment_status', 'pending')
            ->where('created_at', '<', $now->copy()->subDays(7))
            ->count();
        
        $completedPayments = Payment::completed()->get();
        $averagePayment = $completedPayments->count() > 0 
            ? $completedPayments->sum(function ($payment) {
                return $payment->amount - ($payment->refunded_amount ?? 0);
              }) / $completedPayments->count()
            : 0;
        
        return [
            Stat::make('Today\'s Revenue', '$' . number_format($todayRevenue, 2))
                ->description($todayPaymentsCount . ' payment' . ($todayPaymentsCount !== 1 ? 's' : '') . ' processed')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([
                    $this->getDailyNetRevenue($now->copy()->subDays(6)),
                    $this->getDailyNetRevenue($now->copy()->subDays(5)),
                    $this->getDailyNetRevenue($now->copy()->subDays(4)),
                    $this->getDailyNetRevenue($now->copy()->subDays(3)),
                    $this->getDailyNetRevenue($now->copy()->subDays(2)),
                    $this->getDailyNetRevenue($now->copy()->subDays(1)),
                    $todayRevenue,
                ]),
            
            Stat::make('Monthly Revenue', '$' . number_format($monthlyRevenue, 2))
                ->description('Net revenue for ' . $now->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
            
            Stat::make('Pending Payments', $pendingPayments)
                ->description($overduePayments > 0 ? "{$overduePayments} overdue payments" : 'All payments on time')
                ->descriptionIcon('heroicon-m-clock')
                ->color($overduePayments > 0 ? 'danger' : ($pendingPayments > 10 ? 'warning' : 'success')),
            
            Stat::make('Average Payment', '$' . number_format($averagePayment, 2))
                ->description('Average transaction value')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),
        ];
    }

    protected function getDailyNetRevenue($date)
    {
        $payments = Payment::whereDate('paid_at', $date->toDateString())->get();
        
        return $payments->sum(function ($payment) {
            return $payment->amount - ($payment->refunded_amount ?? 0);
        });
    }
}
