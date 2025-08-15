<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Property extends Model
{
    use HasFactory;

    protected $primaryKey = 'property_id';

    protected $fillable = [
        'host_id', 'title', 'description', 'address', 'city', 'country', 
        'latitude', 'longitude', 'photos', 'type', 'price_per_night', 
        'max_guests', 'bedrooms', 'bathrooms', 'amenities', 'images', 
        'status', 'featured'
    ];

    protected $casts = [
        'photos' => 'array',
        'amenities' => 'array',
        'images' => 'array',
        'featured' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
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
        return $this->belongsToMany(
        Amenity::class, 
        'property_amenity', 
        'property_id',     // Foreign key on property_amenity table for this model
        'amenity_id',      // Foreign key on property_amenity table for the related model
        'property_id',     // Local key on properties table
        'amenity_id'       // Local key on amenities table
    );
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
