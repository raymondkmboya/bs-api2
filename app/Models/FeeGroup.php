<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_group_name',
        'type',
        'description',
        'status'
    ];

    protected $casts = [
        'status' => 'string',
        'type' => 'string'
    ];

    public function getActiveAttribute()
    {
        return $this->status === 'active';
    }

    public function getTypeLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->type));
    }
}
