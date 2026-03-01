<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Staff extends Authenticatable
{
    use HasFactory;

    protected $table = 'staffs';

    /**
     * Get the user account associated with the staff.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get department associated with staff.
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($staff) {
            if (empty($staff->staff_id)) {
                $staff->staff_id = $staff->generateStaffId();
            }
        });
    }

    /**
     * Generate staff ID in format YYYY-NNNN
     */
    public function generateStaffId()
    {
        $year = date('Y');
        $lastStaff = static::where('staff_id', 'like', $year . '-%')
                           ->orderBy('staff_id', 'desc')
                           ->first();

        if ($lastStaff) {
            $lastNumber = intval(substr($lastStaff->staff_id, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'staff_id',
        'department_id',
        'position',
        'role',
        'employment_type',
        'hire_date',
        'salary',
        'status',
        'address',
        'city',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'qualifications',
        'experience_years',
        'date_of_birth',
        'gender',
        'nationality',
        'passport_number',
        'work_permit_number',
        'bank_account',
        'bank_name',
        'tax_id',
        'social_security_number',
        'notes'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'date_of_birth' => 'date',
        'salary' => 'decimal:2',
        'experience_years' => 'integer',
        'qualifications' => 'array',
        'email_verified_at' => 'datetime'
    ];

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class, 'teacher_id');
    }

    public function attendance()
    {
        return $this->hasMany(StaffAttendance::class);
    }

    public function leaves()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeTeachers($query)
    {
        return $query->whereIn('position', ['Teacher', 'Senior Teacher', 'Head Teacher']);
    }

    public function scopeAdministrative($query)
    {
        return $query->whereIn('position', ['Principal', 'Vice Principal', 'Administrator', 'Secretary']);
    }

}
