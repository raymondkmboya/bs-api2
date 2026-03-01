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
        'student_photo',
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
        'hear_from_source',
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

    protected $appends = ['student_id'];

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

    // Accessor to get student_id as an alias for id
    public function getStudentIdAttribute()
    {
        return $this->id;
    }

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
     * Get class level of student.
     */
    public function classLevel()
    {
        return $this->belongsTo(ClassLevel::class, 'class_level_id');
    }

    /**
     * Get class level stream of student.
     */
    public function classLevelStream()
    {
        return $this->belongsTo(ClassLevelStream::class, 'class_level_stream_id');
    }

    /**
     * Get payments for this student.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get transactions for this student.
     */
    public function transactions()
    {
        return $this->hasManyThrough(Payment::class, Transaction::class);
    }

    /**
     * Get attendance records for this student.
     */
    public function attendanceRecords()
    {
        return $this->hasMany(StudentAttendanceRecord::class);
    }

    public function followUps()
    {
        return $this->hasMany(RegistrationFollowUp::class);
    }

    public function latestFollowUp()
    {
        return $this->hasOne(RegistrationFollowUp::class)->latest();
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
     * Scope to get registered students with pending follow-ups
     */
    public function scopeFollowedUp($query)
    {
        return $query->where('status', 'registered')
            ->whereHas('followUps', function($followUpQuery) {
                $followUpQuery->where('status', 'pending');
            });
    }

    public function scopeDueTodayFollowedUp($query)
    {
        return $query->where('status', 'registered')
            ->whereHas('followUps', function($followUpQuery) {
                $followUpQuery->where('status', 'pending')
                              ->whereDate('next_follow_up_date', now());
            });
    }

    public function scopeStoppedFollowedUp($query)
    {
        return $query->where('status', 'registered')
            ->whereHas('followUps', function($followUpQuery) {
                $followUpQuery->where('status', 'stopped');
            });
    }

    /**
     * Scope to get admitted students only
     */
    public function scopeAdmitted($query)
    {
        return $query->where('status', 'admitted');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope to get enrolled students only
     */
    public function scopeEnrolled($query)
    {
        return $query->where('status', 'admitted')->whereNotNull('class_level_stream_id');
    }

    public function scopeNotEnrolled($query)
    {
        return $query->where('status', 'admitted')->whereNull('class_level_stream_id');
    }

    /**
     * Scope to get transferred students only
     */
    public function scopeTransferred($query)
    {
        return $query->where('status', 'transferred');
    }

    /**
     * Get subjects for this student.
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'student_subjects');
    }

    /**
     * Scope to get students by stream
     */
    public function scopeByStream($query, $stream)
    {
        return $query->where('stream', $stream);
    }


}
