<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentGuardian extends Model
{
    use HasFactory;

    protected $table = 'parents';

    protected $fillable = [
        'user_id',
        'parent_name',
        'parent_email',
        'parent_phone',
        'address',
        'city',
        'country',
        'occupation',
        'workplace',
        'work_phone',
        'relationship_to_student',
        'emergency_contact_priority',
        'status',
        'notes'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'status' => 'string'
    ];

    /**
     * Get user account associated with parent.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get children (students) of this parent.
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'parent_id', 'parent_id');
    }

    /**
     * Get full name attribute.
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Generate parent ID in format YYYY-NNNN
     */
    public function generateParentId()
    {
        $year = date('Y');
        $lastParent = static::where('parent_id', 'like', $year . '-%')
                           ->orderBy('parent_id', 'desc')
                           ->first();

        if ($lastParent) {
            $lastNumber = intval(substr($lastParent->parent_id, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scope to get active parents only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get parents by relationship type
     */
    public function scopeByRelationship($query, $relationship)
    {
        return $query->where('relationship_to_student', $relationship);
    }
}
