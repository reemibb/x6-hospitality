<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Availability extends Model
{
    use HasFactory;

    protected $table = 'availability';
    protected $primaryKey = 'availability_id';

    protected $fillable = [
        'room_id', 'start_date', 'end_date'
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function getDurationAttribute()
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getIsActiveAttribute()
    {
        $now = Carbon::now()->startOfDay();
        return $this->start_date <= $now && $this->end_date >= $now;
    }

    public function getStatusAttribute()
    {
        $now = Carbon::now()->startOfDay();
        
        if ($this->end_date < $now) {
            return 'expired';
        } elseif ($this->start_date <= $now && $this->end_date >= $now) {
            return 'active';
        } else {
            return 'upcoming';
        }
    }

    public function scopeActive($query)
    {
        $now = Carbon::now()->startOfDay();
        return $query->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
    }

    public function scopeUpcoming($query)
    {
        $now = Carbon::now()->startOfDay();
        return $query->where('start_date', '>', $now);
    }

    public function scopeExpired($query)
    {
        $now = Carbon::now()->startOfDay();
        return $query->where('end_date', '<', $now);
    }
}
