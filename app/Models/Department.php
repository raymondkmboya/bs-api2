<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_name',
        'hod_id',
        'description',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the head of department for this department.
     */
    public function headOfDepartment(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'hod_id');
    }

    /**
     * Get the staff members in this department.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'department_id', 'department_name');
    }

    /**
     * Scope a query to only include active departments.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include inactive departments.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Get the formatted status with proper casing.
     */
    public function getFormattedStatusAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Get the total number of staff in the department.
     */
    public function getStaffCountAttribute(): int
    {
        return $this->staff()->count();
    }
}
