<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolEnquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'level_interested',
        'source',
        'status',
        'message',
        'follow_up_date',
        'notes'
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'status' => 'string',
        'source' => 'string'
    ];

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getSourceLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->source));
    }

    public function getLevelInterestedLabelAttribute()
    {
        return $this->level_interested;
    }
}
