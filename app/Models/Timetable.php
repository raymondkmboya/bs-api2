<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_level_stream_id',
        'subject_id',
        'teacher_id',
        'day_of_week',
        'time_slot',
        'room_id',
        'created_by'
    ];

    protected $casts = [
        'status' => 'string',
        'academic_year' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the class level associated with this timetable entry.
     */
    public function classLevel()
    {
        return $this->belongsTo(ClassLevel::class);
    }

    /**
     * Get the class stream associated with this timetable entry.
     */
    public function classStream()
    {
        return $this->belongsTo(ClassLevelStream::class, 'class_level_stream_id');
    }

    /**
     * Get the subject associated with this timetable entry.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the teacher associated with this timetable entry.
     */
    public function teacher()
    {
        return $this->belongsTo(Staff::class, 'teacher_id');
    }

    /**
     * Get the room associated with this timetable entry.
     */
    public function room()
    {
        return $this->belongsTo(Classroom::class, 'room_id');
    }

    /**
     * Get the user who created this timetable entry.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get entries by class level.
     */
    public function scopeByClassLevel($query, $classLevelId)
    {
        return $query->where('class_level_id', $classLevelId);
    }

    /**
     * Scope to get entries by class stream.
     */
    public function scopeByStream($query, $streamId)
    {
        return $query->where('class_level_stream_id', $streamId);
    }

    /**
     * Scope to get entries by subject.
     */
    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    /**
     * Scope to get entries by teacher.
     */
    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Scope to get entries by day of week.
     */
    public function scopeByDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope to get entries by time slot.
     */
    public function scopeByTimeSlot($query, $timeSlot)
    {
        return $query->where('time_slot', $timeSlot);
    }

    /**
     * Scope to get entries by academic year.
     */
    public function scopeByAcademicYear($query, $academicYear)
    {
        return $query->where('academic_year', $academicYear);
    }

    /**
     * Scope to get entries by semester.
     */
    public function scopeBySemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }

    /**
     * Scope to get active entries.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get entries for a specific time slot and day.
     */
    public function scopeForSlot($query, $dayOfWeek, $timeSlot)
    {
        return $query->where('day_of_week', $dayOfWeek)
                   ->where('time_slot', $timeSlot);
    }

    /**
     * Check if this is a morning slot.
     */
    public function getIsMorningAttribute()
    {
        $time = $this->time_slot;
        $hour = (int) substr($time, 0, 2);
        return $hour < 12;
    }

    /**
     * Get formatted time slot.
     */
    public function getFormattedTimeAttribute()
    {
        $time = $this->time_slot;
        return date('h:i A', strtotime($time));
    }

    /**
     * Get day name from day number.
     */
    public function getDayNameAttribute()
    {
        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday'
        ];

        return $days[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Check for scheduling conflicts.
     */
    public function hasConflict($teacherId = null, $roomId = null, $dayOfWeek = null, $timeSlot = null)
    {
        $teacherId = $teacherId ?? $this->teacher_id;
        $roomId = $roomId ?? $this->room_id;
        $dayOfWeek = $dayOfWeek ?? $this->day_of_week;
        $timeSlot = $timeSlot ?? $this->time_slot;

        // Check teacher conflict
        $teacherConflict = static::where('teacher_id', $teacherId)
            ->where('day_of_week', $dayOfWeek)
            ->where('time_slot', $timeSlot)
            ->where('status', 'active')
            ->where('id', '!=', $this->id ?? 0)
            ->exists();

        // Check room conflict
        $roomConflict = static::where('room_id', $roomId)
            ->where('day_of_week', $dayOfWeek)
            ->where('time_slot', $timeSlot)
            ->where('status', 'active')
            ->where('id', '!=', $this->id ?? 0)
            ->exists();

        return $teacherConflict || $roomConflict;
    }

    /**
     * Get available time slots.
     */
    public static function getAvailableTimeSlots()
    {
        return [
            '07:00' => '7:00 AM',
            '08:00' => '8:00 AM',
            '09:00' => '9:00 AM',
            '10:00' => '10:00 AM',
            '11:00' => '11:00 AM',
            '12:00' => '12:00 PM',
            '13:00' => '1:00 PM',
            '14:00' => '2:00 PM',
            '15:00' => '3:00 PM',
            '16:00' => '4:00 PM',
            '17:00' => '5:00 PM'
        ];
    }

    /**
     * Get days of week.
     */
    public static function getDaysOfWeek()
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday'
        ];
    }

    /**
     * Get semesters.
     */
    public static function getSemesters()
    {
        return [
            '1' => 'First Semester',
            '2' => 'Second Semester',
            '3' => 'Third Semester'
        ];
    }

    /**
     * Get statuses.
     */
    public static function getStatuses()
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'cancelled' => 'Cancelled'
        ];
    }
}
