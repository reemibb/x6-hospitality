<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; 

class FinancialAnalytics extends BaseWidget
{
    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $now = Carbon::now();
        
        $paymentMethodStats = DB::table('payments')
            ->select('payment_method', 
                DB::raw('SUM(amount) as gross_amount'), 
                DB::raw('SUM(IFNULL(refunded_amount, 0)) as refunded_amount'),
                DB::raw('COUNT(*) as count'))
            ->whereIn('payment_status', ['completed', 'paid', 'partially_refunded', 'refunded'])
            ->groupBy('payment_method')
            ->get()
            ->map(function($item) {
                $item->net_amount = $item->gross_amount - $item->refunded_amount;
                return $item;
            })
            ->sortByDesc('net_amount');
            
        $topPaymentMethod = $paymentMethodStats->first();
            
        $failedPayments = Payment::failed()->count();
        $totalPayments = Payment::count();
        $successRate = $totalPayments > 0 ? round((($totalPayments - $failedPayments) / $totalPayments) * 100, 1) : 0;
        
        $totalGrossAmount = Payment::whereIn('payment_status', ['completed', 'paid', 'partially_refunded', 'refunded'])
            ->sum('amount');
            
        $totalRefundedAmount = Payment::whereIn('payment_status', ['partially_refunded', 'refunded'])
            ->sum('refunded_amount');
            
        $refundRate = $totalGrossAmount > 0 ? round(($totalRefundedAmount / $totalGrossAmount) * 100, 1) : 0;
        
        return [
            Stat::make('Success Rate', $successRate . '%')
                ->description("Out of {$totalPayments} total payments")
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($successRate >= 95 ? 'success' : ($successRate >= 85 ? 'warning' : 'danger')),
            
            Stat::make('Top Payment Method', $topPaymentMethod ? str_replace('_', ' ', Str::title($topPaymentMethod->payment_method)) : 'N/A')
                ->description($topPaymentMethod ? "$" . number_format($topPaymentMethod->net_amount, 2) . " net revenue" : 'No data')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('info'),
            
            Stat::make('Refund Rate', $refundRate . '%')
                ->description('$' . number_format($totalRefundedAmount, 2) . ' refunded')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($refundRate <= 5 ? 'success' : ($refundRate <= 15 ? 'warning' : 'danger')),
            
            Stat::make('Failed Payments', $failedPayments)
                ->description('Require attention')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($failedPayments === 0 ? 'success' : ($failedPayments <= 5 ? 'warning' : 'danger')),
        ];
    }
}
