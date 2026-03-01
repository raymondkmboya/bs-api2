<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_name',
        'exam_date',
        'exam_type',
        'subject_id',
        'duration',
        'status',
        'class_level_stream_id',
        'total_marks',
        'passing_marks',
        'instructions',
        'created_by',
        'academic_year',
    ];

    protected $casts = [
        'exam_date' => 'datetime',
        'duration' => 'integer',
        'total_marks' => 'integer',
        'passing_marks' => 'integer',
        'status' => 'string'
    ];

    /**
     * Get the subject associated with the exam.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the class level associated with the exam.
     */
    public function classLevel()
    {
        return $this->belongsTo(ClassLevel::class);
    }

    /**
     * Get the class stream associated with the exam.
     */
    public function classStream()
    {
        return $this->belongsTo(ClassLevelStream::class, 'class_level_stream_id');
    }

    /**
     * Get the user who created the exam.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get exam results for this exam.
     */
    public function results()
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Scope to get exams by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get upcoming exams.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('exam_date', '>', now());
    }

    /**
     * Scope to get past exams.
     */
    public function scopePast($query)
    {
        return $query->where('exam_date', '<', now());
    }

    /**
     * Scope to get exams for a specific class level.
     */
    public function scopeForClassLevel($query, $classLevelId)
    {
        return $query->where('class_level_id', $classLevelId);
    }

    /**
     * Scope to get exams for a specific stream.
     */
    public function scopeForStream($query, $streamId)
    {
        return $query->where('class_level_stream_id', $streamId);
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute()
    {
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;
        
        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }

    /**
     * Get exam status badge color.
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'scheduled' => 'blue',
            'ongoing' => 'orange', 
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray'
        };
    }
}
