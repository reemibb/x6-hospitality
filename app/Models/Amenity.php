<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    use HasFactory;

    protected $primaryKey = 'amenity_id';

    protected $fillable = [
        'name', 'description'
    ];

    public function properties()
    {
        return $this->belongsToMany(
        Property::class, 
        'property_amenity', 
        'amenity_id',      // Foreign key on property_amenity table for this model
        'property_id',     // Foreign key on property_amenity table for the related model
        'amenity_id',      // Local key on amenities table
        'property_id'      // Local key on properties table
    );
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'room_amenity');
    }
}
