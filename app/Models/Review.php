<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Review extends Model
{
    use HasFactory;

    protected $primaryKey = 'review_id';

    protected $fillable = [
        'user_id', 'property_id', 'booking_id', 'rating', 'comment', 'status', 'response', 'responded_at', 'helpful_votes', 'verified'
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'responded_at' => 'datetime',
            'helpful_votes' => 'integer',
            'verified' => 'boolean',
        ];
    }

    public function guest()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id', 'property_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function getRatingTextAttribute()
    {
        return match($this->rating) {
            5 => 'Excellent',
            4 => 'Very Good',
            3 => 'Good',
            2 => 'Fair',
            1 => 'Poor',
            default => 'No Rating',
        };
    }

    public function getRatingColorAttribute()
    {
        return match($this->rating) {
            5 => 'success',
            4 => 'info',
            3 => 'warning',
            2 => 'orange',
            1 => 'danger',
            default => 'gray',
        };
    }

    public function getStatusBadgeColorAttribute()
    {
        return match($this->status ?? 'published') {
            'published' => 'success',
            'pending' => 'warning',
            'flagged' => 'danger',
            'hidden' => 'gray',
            default => 'primary',
        };
    }

    public function getStarsAttribute()
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    public function getIsRecentAttribute()
    {
        return $this->created_at && $this->created_at->diffInDays(now()) <= 30;
    }

    public function getIsVerifiedGuestAttribute()
    {
        return $this->booking && 
               $this->booking->status === 'confirmed' && 
               $this->booking->check_out_date < now();
    }

    public function getWordCountAttribute()
    {
        return $this->comment ? str_word_count(strip_tags($this->comment)) : 0;
    }

    public function getHasResponseAttribute()
    {
        return !empty($this->response);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFlagged($query)
    {
        return $query->where('status', 'flagged');
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeHighRated($query)
    {
        return $query->where('rating', '>=', 4);
    }

    public function scopeLowRated($query)
    {
        return $query->where('rating', '<=', 2);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopeWithResponse($query)
    {
        return $query->whereNotNull('response');
    }

    public function scopeWithoutResponse($query)
    {
        return $query->whereNull('response');
    }
}
