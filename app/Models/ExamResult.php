<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'marks_obtained',
        'grade',
        'remarks',
        'status',
        'submitted_by',
        'submission_date'
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'submission_date' => 'datetime',
        'status' => 'string'
    ];

    /**
     * Get the exam associated with this result.
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the student associated with this result.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the user who submitted this result.
     */
    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Scope to get results by exam.
     */
    public function scopeByExam($query, $examId)
    {
        return $query->where('exam_id', $examId);
    }

    /**
     * Scope to get results by student.
     */
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to get results by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Calculate percentage based on marks obtained and exam total marks.
     */
    public function getPercentageAttribute()
    {
        if (!$this->exam || $this->exam->total_marks == 0) {
            return 0;
        }
        
        return ($this->marks_obtained / $this->exam->total_marks) * 100;
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pass' => 'green',
            'fail' => 'red',
            'pending' => 'orange',
            'absent' => 'gray',
            default => 'gray'
        };
    }

    /**
     * Get grade color for UI.
     */
    public function getGradeColorAttribute()
    {
        return match($this->grade) {
            'A', 'A+' => 'green',
            'B+', 'B' => 'blue',
            'C' => 'orange',
            'D', 'E' => 'red',
            'F' => 'red',
            default => 'gray'
        };
    }

    /**
     * Determine if student passed based on exam passing marks.
     */
    public function getPassedAttribute()
    {
        if (!$this->exam) {
            return false;
        }
        
        return $this->marks_obtained >= $this->exam->passing_marks;
    }

    /**
     * Auto-calculate grade based on percentage.
     */
    public function calculateGrade()
    {
        $percentage = $this->percentage;
        
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 60) return 'C';
        if ($percentage >= 50) return 'D';
        if ($percentage >= 40) return 'E';
        return 'F';
    }

    /**
     * Auto-generate remarks based on performance.
     */
    public function generateRemarks()
    {
        $percentage = $this->percentage;
        
        if ($percentage >= 80) return 'Excellent Performance';
        if ($percentage >= 70) return 'Very Good';
        if ($percentage >= 60) return 'Good';
        if ($percentage >= 50) return 'Satisfactory';
        if ($percentage >= 40) return 'Needs Improvement';
        return 'Poor Performance';
    }
}
