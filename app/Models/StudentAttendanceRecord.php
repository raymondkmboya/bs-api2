<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'student_name',
        'level',
        'stream',
        'class',
        'scan_time',
        'check_in_time',
        'status',
        'scan_method',
        'device',
        'attendance_type'
    ];

    protected $casts = [
        'scan_time' => 'datetime',
        'status' => 'string',
        'scan_method' => 'string',
        'attendance_type' => 'string'
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    // Scopes for common queries
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('scan_time', $date);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeByStream($query, $stream)
    {
        return $query->where('stream', $stream);
    }

    public function scopeByClass($query, $class)
    {
        return $query->where('class', $class);
    }

    // Accessors
    public function getFormattedScanTimeAttribute()
    {
        return $this->scan_time->format('Y-m-d H:i:s');
    }

    public function getScanDateAttribute()
    {
        return $this->scan_time->format('Y-m-d');
    }

    public function getCheckInTimeFormattedAttribute()
    {
        return $this->scan_time->format('h:i A');
    }
}
