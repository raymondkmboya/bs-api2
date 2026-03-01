<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationFollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'follow_up_date',
        'medium_used',
        'message_content',
        'next_follow_up_date',
        'status',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'next_follow_up_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes for common queries
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['enrolled', 'stop_follow_up']);
    }

    public function scopeDueForFollowUp($query)
    {
        return $query->where('next_follow_up_date', '<=', now())
                    ->where('status', '!=', 'stop_follow_up');
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }
}
