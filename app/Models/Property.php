<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'host_id', 'title', 'description', 'address', 'city', 'country', 'latitude', 'longitude', 'photos'
    ];

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'property_amenity');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function availability()
    {
        return $this->hasMany(Availability::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
}
