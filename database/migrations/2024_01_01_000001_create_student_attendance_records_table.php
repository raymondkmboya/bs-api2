<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 50);
            $table->string('student_name');
            $table->string('level', 50);
            $table->string('stream', 50);
            $table->string('class', 50);
            $table->timestamp('scan_time');
            $table->string('check_in_time', 20);
            $table->enum('status', ['present', 'late', 'absent', 'half_day', 'excused'])->default('present');
            $table->string('scan_method', 50); // ID Card, Fingerprint, Face Recognition, QR Code
            $table->string('device', 50); // Scanner 01, Scanner 02, etc.
            $table->string('attendance_type', 50); // Morning Check-in, Afternoon Check-in
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['student_id', 'scan_time']);
            $table->index(['level', 'stream', 'class']);
            $table->index('scan_time');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendance_records');
    }
};
