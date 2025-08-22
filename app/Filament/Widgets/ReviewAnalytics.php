<?php

namespace App\Filament\Widgets;

use App\Models\Review;
use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReviewAnalytics extends BaseWidget
{
    protected static ?int $sort = 7;

    protected function getStats(): array
    {
        $fiveStarReviews = Review::published()->where('rating', 5)->count();
        $fourStarReviews = Review::published()->where('rating', 4)->count();
        $lowRatedReviews = Review::published()->where('rating', '<=', 2)->count();
        $totalPublished = Review::published()->count();
        
        $fiveStarPercentage = $totalPublished > 0 ? round(($fiveStarReviews / $totalPublished) * 100, 1) : 0;
        
        $topProperty = null;
        
        if ($totalPublished > 0) {
            $propertiesWithReviews = Property::select('properties.*')
                ->selectRaw('COUNT(reviews.review_id) as reviews_count')
                ->selectRaw('AVG(reviews.rating) as reviews_avg_rating')
                ->join('reviews', 'properties.property_id', '=', 'reviews.property_id')
                ->where('reviews.status', 'published')
                ->groupBy('properties.property_id')
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->first();
                
            $topProperty = $propertiesWithReviews;
        }
        
        $verifiedReviews = Review::verified()->published()->count();
        $verificationRate = $totalPublished > 0 ? round(($verifiedReviews / $totalPublished) * 100, 1) : 0;
        
        return [
            Stat::make('5-Star Reviews', $fiveStarPercentage . '%')
                ->description("{$fiveStarReviews} excellent reviews")
                ->descriptionIcon('heroicon-m-star')
                ->color($fiveStarPercentage >= 60 ? 'success' : ($fiveStarPercentage >= 40 ? 'warning' : 'danger')),
            
            Stat::make('Top Rated Property', $topProperty ? $topProperty->title : 'N/A')
                ->description($topProperty ? number_format($topProperty->reviews_avg_rating, 1) . '⭐ (' . $topProperty->reviews_count . ' reviews)' : 'No reviews yet')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),
            
            Stat::make('Low Ratings', $lowRatedReviews)
                ->description('Reviews with 1-2 stars (need attention)')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowRatedReviews === 0 ? 'success' : ($lowRatedReviews <= 5 ? 'warning' : 'danger')),
            
            Stat::make('Verified Reviews', $verificationRate . '%')
                ->description("{$verifiedReviews} verified guest reviews")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($verificationRate >= 80 ? 'success' : ($verificationRate >= 60 ? 'warning' : 'danger')),
        ];
    }
}