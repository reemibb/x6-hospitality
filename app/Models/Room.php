<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id', 'room_type', 'price_per_night', 'description', 'photos'
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'room_amenity');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function availability()
    {
        return $this->hasMany(Availability::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
