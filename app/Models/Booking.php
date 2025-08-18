<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory;

    protected $primaryKey = 'booking_id';

    protected $fillable = [
        'user_id', 'room_id', 'check_in_date', 'check_out_date', 'guests_count', 'status', 'total_price'
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'total_price' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'booking_id', 'booking_id');
    }

    public function getDurationAttribute()
    {
        return $this->check_in_date->diffInDays($this->check_out_date);
    }

    public function getBookingReferenceAttribute()
    {
        return 'BK-' . str_pad($this->booking_id, 6, '0', STR_PAD_LEFT);
    }

    public function getIsActiveAttribute()
    {
        $now = Carbon::now()->startOfDay();
        return $this->check_in_date <= $now && $this->check_out_date > $now && $this->status === 'confirmed';
    }

    public function getBookingStatusAttribute()
    {
        $now = Carbon::now()->startOfDay();
        
        if ($this->status === 'cancelled') {
            return 'cancelled';
        } elseif ($this->status === 'pending') {
            return 'pending';
        } elseif ($this->check_out_date < $now) {
            return 'completed';
        } elseif ($this->check_in_date <= $now && $this->check_out_date > $now) {
            return 'active';
        } else {
            return 'upcoming';
        }
    }

    public function scopeActive($query)
    {
        $now = Carbon::now()->startOfDay();
        return $query->where('check_in_date', '<=', $now)
                    ->where('check_out_date', '>', $now)
                    ->where('status', 'confirmed');
    }

    public function scopeUpcoming($query)
    {
        $now = Carbon::now()->startOfDay();
        return $query->where('check_in_date', '>', $now)
                    ->where('status', 'confirmed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        $now = Carbon::now()->startOfDay();
        return $query->where('check_out_date', '<', $now)
                    ->where('status', 'confirmed');
    }
}
