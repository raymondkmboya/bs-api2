<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Advertisement extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'cost',
        'medium',
        'advert_date',
        'description',
        'status',
        'notes'
    ];

    protected $casts = [
        'advert_date' => 'date',
        'cost' => 'decimal:2'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getUserAttribute()
    {
        return $this->staff ? $this->staff->first_name . ' ' . $this->staff->last_name : 'Unknown';
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }
}
