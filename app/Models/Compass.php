<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compass extends Model
{
    use HasFactory;

    protected $table = 'compass';

    protected $fillable = [
        'name',
        'description',
        'building',
        'floor',
        'supervisor',
        'capacity',
        'status'
    ];

    protected $casts = [
        'capacity' => 'integer',
        'status' => 'string'
    ];

    public function classrooms()
    {
        return $this->hasMany(Classroom::class);
    }
}
