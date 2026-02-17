<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassLevelStream extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'class_level_id',
        'description',
        'capacity',
        'status'
    ];

    protected $casts = [
        'class_level_id' => 'integer',
        'capacity' => 'integer',
        'status' => 'string'
    ];

    public function classLevel()
    {
        return $this->belongsTo(ClassLevel::class);
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class);
    }
}
