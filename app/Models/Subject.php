<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'subject_name',
        'subject_code',
        'description',
        'class_level_id',
        'teacher_id',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the class level that owns the subject.
     */
    public function classLevel()
    {
        return $this->belongsTo(ClassLevel::class);
    }

    /**
     * Get the teacher for this subject.
     */
    public function subjectTeacher()
    {
        return $this->belongsTo(Staff::class, 'teacher_id');
    }

    /**
     * Get the timetable entries for this subject.
     */
    public function timetableEntries()
    {
        return $this->hasMany(TimetableEntry::class);
    }

    /**
     * Get students enrolled in this subject.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_subjects')
            ->withPivot(['class_level_id', 'academic_year', 'semester'])
            ->withTimestamps();
    }

        /**
     * Get student count for this subject.
     */
    public function studentCount()
    {
        return $this->students()->count();
    }

}
