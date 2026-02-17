<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_level_name',
        'description',
        'level_number',
        'status'
    ];

    protected $casts = [
        'level_number' => 'integer',
        'status' => 'string'
    ];

    public function streams()
    {
        return $this->hasMany(ClassLevelStream::class);
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class);
    }
}
