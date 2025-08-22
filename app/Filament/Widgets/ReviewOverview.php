<?php

namespace App\Filament\Widgets;

use App\Models\Review;
use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReviewOverview extends BaseWidget
{
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $now = Carbon::now();
        
        $totalReviews = Review::count();
        $publishedReviews = Review::published()->count();
        $pendingReviews = Review::pending()->count();
        $flaggedReviews = Review::flagged()->count();
        
        $averageRating = Review::published()->avg('rating') ?? 0;
        
        $recentReviews = Review::recent(30)->count();
        $recentPublished = Review::recent(30)->published()->count();
        
        $reviewsWithResponse = Review::published()->whereNotNull('response')->count();
        $responseRate = $publishedReviews > 0 ? round(($reviewsWithResponse / $publishedReviews) * 100, 1) : 0;
        
        return [
            Stat::make('Total Reviews', number_format($totalReviews))
                ->description($publishedReviews . ' published, ' . $pendingReviews . ' pending')
                ->descriptionIcon('heroicon-m-star')
                ->color('primary')
                ->chart([
                    Review::whereDate('created_at', $now->copy()->subDays(6))->count(),
                    Review::whereDate('created_at', $now->copy()->subDays(5))->count(),
                    Review::whereDate('created_at', $now->copy()->subDays(4))->count(),
                    Review::whereDate('created_at', $now->copy()->subDays(3))->count(),
                    Review::whereDate('created_at', $now->copy()->subDays(2))->count(),
                    Review::whereDate('created_at', $now->copy()->subDays(1))->count(),
                    Review::whereDate('created_at', $now)->count(),
                ]),
            
            Stat::make('Average Rating', number_format($averageRating, 1) . '⭐')
                ->description('Overall guest satisfaction')
                ->descriptionIcon('heroicon-m-face-smile')
                ->color($averageRating >= 4.5 ? 'success' : ($averageRating >= 4.0 ? 'info' : ($averageRating >= 3.5 ? 'warning' : 'danger'))),
            
            Stat::make('Recent Reviews (30d)', $recentReviews)
                ->description($recentPublished . ' published this month')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
            
            Stat::make('Response Rate', $responseRate . '%')
                ->description($reviewsWithResponse . ' reviews responded to')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color($responseRate >= 80 ? 'success' : ($responseRate >= 60 ? 'warning' : 'danger')),
        ];
    }
}
