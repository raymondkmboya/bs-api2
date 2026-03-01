<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_group_id',
        'class_level_id',
        'fee_name',
        'amount',
        'initial_amount',
        'installments',
        'academic_year',
        'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'academic_year' => 'string',
        'status' => 'string'
    ];

    public function feeGroup()
    {
        return $this->belongsTo(FeeGroup::class, 'fee_group_id');
    }

    public function classLevel()
    {
        return $this->belongsTo(ClassLevel::class, 'class_level_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'fee_structure_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByClassLevel($query, $classLevelId)
    {
        return $query->where('class_level_id', $classLevelId);
    }

    public function scopeByFeeGroup($query, $feeGroupId)
    {
        return $query->where('fee_group_id', $feeGroupId);
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function getDueDateTypeLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->due_date_type));
    }
}
