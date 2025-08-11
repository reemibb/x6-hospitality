<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description'
    ];

    public function properties()
    {
        return $this->belongsToMany(Property::class, 'property_amenity');
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'room_amenity');
    }
}
