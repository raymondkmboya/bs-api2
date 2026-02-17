<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    use HasFactory;

    protected $table = 'students';

    protected $fillable = [
        'user_id',
        'student_number',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'class_level_id',
        'class_level_stream_id',
        'region',
        'address',
        'parent_id',
        'status',
        'registration_date',
        'admission_date',
        'enrollment_date',
        'graduation_date',
        'profile_image',
        'admission_number',
        'registration_data',
        'admission_data',
        'enrollment_data'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'status' => 'string',
        'registration_date' => 'date'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($student) {
            if (empty($student->student_number)) {
                $student->student_number = $student->generateStudentNumber();
            }
        });
    }

    /**
     * Get user account associated with student.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get parent/guardian of student.
     */
    public function parent()
    {
        return $this->belongsTo(ParentGuardian::class, 'parent_id');
    }

    /**
     * Generate student number in format YYYY-NNNN
     */
    public function generateStudentNumber()
    {
        $year = date('Y');
        $lastStudent = static::where('student_number', 'like', $year . '-%')
                           ->orderBy('student_number', 'desc')
                           ->first();

        if ($lastStudent) {
            $lastNumber = intval(substr($lastStudent->student_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function attendanceRecords()
    {
        return $this->hasMany(StudentAttendanceRecord::class, 'student_id', 'student_id');
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getActiveAttribute()
    {
        return $this->status === 'active';
    }

    /**
     * Scope to get registered students only
     */
    public function scopeRegistered($query)
    {
        return $query->where('status', 'registered');
    }

    /**
     * Scope to get admitted students only
     */
    public function scopeAdmitted($query)
    {
        return $query->where('status', 'admitted');
    }

    /**
     * Scope to get enrolled students only
     */
    public function scopeEnrolled($query)
    {
        return $query->where('status', 'enrolled');
    }

    /**
     * Scope to get transferred students only
     */
    public function scopeTransferred($query)
    {
        return $query->where('status', 'transferred');
    }

    /**
     * Scope to get students by stream
     */
    public function scopeByStream($query, $stream)
    {
        return $query->where('stream', $stream);
    }
}
