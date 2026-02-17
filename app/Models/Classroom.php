<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    protected $fillable = [
        'name',
        'room_number',
        'capacity',
        'building',
        'floor',
        'classroom_type',
        'status',
        'description',
        'facilities'
    ];

    protected $casts = [
        'facilities' => 'array',
        'capacity' => 'integer'
    ];
}
