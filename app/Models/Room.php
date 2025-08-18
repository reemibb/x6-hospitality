<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;

    protected $primaryKey = 'room_id';

    protected $fillable = [
        'property_id', 'room_type', 'price_per_night', 'description', 'photos'
    ];

    protected $casts = [
        'photos' => 'array',
        'price_per_night' => 'decimal:2',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id', 'property_id');
    }

    public function amenities()
    {
        return $this->belongsToMany(
            Amenity::class, 
            'room_amenity', 
            'room_id',
            'amenity_id',
            'room_id',
            'amenity_id'
        );
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'room_id', 'room_id');
    }

    public function availability()
    {
        return $this->hasMany(Availability::class, 'room_id', 'room_id');
    }

    public function getDisplayNameAttribute()
    {
        return $this->property->title . ' - ' . $this->room_type . ' (ID: ' . $this->room_id . ')';
    }

    public function getCurrentAvailabilityAttribute()
    {
        return $this->availability()
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
    }
}
