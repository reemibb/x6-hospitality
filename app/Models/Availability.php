<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Availability extends Model
{
    use HasFactory;

    protected $primaryKey = 'availability_id';

    protected $fillable = [
        'room_id', 'start_date', 'end_date'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
