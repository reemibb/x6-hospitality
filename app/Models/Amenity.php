<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    use HasFactory;

    protected $primaryKey = 'amenity_id';

    protected $fillable = [
        'name', 'description', 'category'
    ];

    public function properties()
    {
        return $this->belongsToMany(
            Property::class, 
            'property_amenity', 
            'amenity_id',
            'property_id',
            'amenity_id',
            'property_id'
        );
    }

    public function rooms()
    {
        return $this->belongsToMany(
            Room::class, 
            'room_amenity', 
            'amenity_id',
            'room_id',
            'amenity_id',
            'room_id'
        );
    }

    public function scopePropertyAmenities($query)
    {
        return $query->whereIn('category', ['property', 'both']);
    }

    public function scopeRoomAmenities($query)
    {
        return $query->whereIn('category', ['room', 'both']);
    }
}
