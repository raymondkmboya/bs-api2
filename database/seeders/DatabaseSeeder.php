<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            ClassroomSeeder::class,
            FeeGroupSeeder::class,
            StudentSeeder::class,
            StudentAttendanceSeeder::class,
            SchoolEnquirySeeder::class,
        ]);

        // Create test user for API authentication
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}

class FeeGroupSeeder extends Seeder
{
    public function run(): void
    {
        $feeGroups = [
            [
                'name' => 'Tuition Fees',
                'type' => 'mandatory',
                'description' => 'Regular tuition fees for all levels',
                'amount' => 500000.00,
                'status' => 'active'
            ],
            [
                'name' => 'Exam Fees',
                'type' => 'mandatory',
                'description' => 'Examination fees for QT, CSEE, and ASCEE',
                'amount' => 150000.00,
                'status' => 'active'
            ],
            [
                'name' => 'Registration Fees',
                'type' => 'one_time',
                'description' => 'One-time registration fee for new students',
                'amount' => 100000.00,
                'status' => 'active'
            ],
            [
                'name' => 'Sports Activities',
                'type' => 'optional',
                'description' => 'Optional sports and extracurricular activities',
                'amount' => 75000.00,
                'status' => 'active'
            ],
            [
                'name' => 'Library Fees',
                'type' => 'mandatory',
                'description' => 'Library and learning resource fees',
                'amount' => 50000.00,
                'status' => 'active'
            ]
        ];

        foreach ($feeGroups as $feeGroup) {
            \App\Models\FeeGroup::create($feeGroup);
        }
    }
}

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            [
                'student_id' => 'STU001',
                'first_name' => 'John Michael',
                'last_name' => 'Kimario',
                'email' => 'john.kimario@school.com',
                'phone' => '+255712345678',
                'date_of_birth' => '2008-03-15',
                'gender' => 'male',
                'level' => 'QT',
                'stream' => 'Stream A',
                'class' => 'Class 1A',
                'address' => 'Dar es Salaam, Tanzania',
                'parent_name' => 'Joseph Kimario',
                'parent_phone' => '+255712345679',
                'admission_date' => '2023-01-15',
                'status' => 'active'
            ],
            [
                'student_id' => 'STU002',
                'first_name' => 'Grace Esther',
                'last_name' => 'Mwangi',
                'email' => 'grace.mwangi@school.com',
                'phone' => '+255712345680',
                'date_of_birth' => '2008-07-22',
                'gender' => 'female',
                'level' => 'QT',
                'stream' => 'Stream A',
                'class' => 'Class 1A',
                'address' => 'Dar es Salaam, Tanzania',
                'parent_name' => 'Mary Mwangi',
                'parent_phone' => '+255712345681',
                'admission_date' => '2023-01-15',
                'status' => 'active'
            ],
            [
                'student_id' => 'STU003',
                'first_name' => 'Peter James',
                'last_name' => 'Mollel',
                'email' => 'peter.mollel@school.com',
                'phone' => '+255712345682',
                'date_of_birth' => '2007-11-10',
                'gender' => 'male',
                'level' => 'QT',
                'stream' => 'Stream B',
                'class' => 'Class 1C',
                'address' => 'Arusha, Tanzania',
                'parent_name' => 'James Mollel',
                'parent_phone' => '+255712345683',
                'admission_date' => '2023-01-15',
                'status' => 'active'
            ],
            [
                'student_id' => 'STU004',
                'first_name' => 'Anna Faith',
                'last_name' => 'Nyambura',
                'email' => 'anna.nyambura@school.com',
                'phone' => '+255712345684',
                'date_of_birth' => '2006-05-18',
                'gender' => 'female',
                'level' => 'CSEE',
                'stream' => 'Stream A',
                'class' => 'Class 2A',
                'address' => 'Dar es Salaam, Tanzania',
                'parent_name' => 'David Nyambura',
                'parent_phone' => '+255712345685',
                'admission_date' => '2022-01-15',
                'status' => 'active'
            ],
            [
                'student_id' => 'STU005',
                'first_name' => 'David Michael',
                'last_name' => 'Kinyua',
                'email' => 'david.kinyua@school.com',
                'phone' => '+255712345686',
                'date_of_birth' => '2006-09-25',
                'gender' => 'male',
                'level' => 'CSEE',
                'stream' => 'Stream B',
                'class' => 'Class 2D',
                'address' => 'Nairobi, Kenya',
                'parent_name' => 'Michael Kinyua',
                'parent_phone' => '+255712345687',
                'admission_date' => '2022-01-15',
                'status' => 'active'
            ]
        ];

        foreach ($students as $student) {
            \App\Models\Student::create($student);
        }
    }
}

class StudentAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $attendanceRecords = [
            [
                'student_id' => 'STU001',
                'student_name' => 'John Michael Kimario',
                'level' => 'QT',
                'stream' => 'Stream A',
                'class' => 'Class 1A',
                'scan_time' => now()->setHours(7, 30, 15),
                'check_in_time' => '07:30 AM',
                'status' => 'present',
                'scan_method' => 'ID Card',
                'device' => 'Scanner 01',
                'attendance_type' => 'Morning Check-in'
            ],
            [
                'student_id' => 'STU002',
                'student_name' => 'Grace Esther Mwangi',
                'level' => 'QT',
                'stream' => 'Stream A',
                'class' => 'Class 1A',
                'scan_time' => now()->setHours(7, 32, 45),
                'check_in_time' => '07:32 AM',
                'status' => 'present',
                'scan_method' => 'ID Card',
                'device' => 'Scanner 01',
                'attendance_type' => 'Morning Check-in'
            ],
            [
                'student_id' => 'STU003',
                'student_name' => 'Peter James Mollel',
                'level' => 'QT',
                'stream' => 'Stream B',
                'class' => 'Class 1C',
                'scan_time' => now()->setHours(7, 45, 20),
                'check_in_time' => '07:45 AM',
                'status' => 'late',
                'scan_method' => 'Fingerprint',
                'device' => 'Scanner 02',
                'attendance_type' => 'Morning Check-in'
            ],
            [
                'student_id' => 'STU004',
                'student_name' => 'Anna Faith Nyambura',
                'level' => 'CSEE',
                'stream' => 'Stream A',
                'class' => 'Class 2A',
                'scan_time' => now()->setHours(7, 28, 30),
                'check_in_time' => '07:28 AM',
                'status' => 'present',
                'scan_method' => 'ID Card',
                'device' => 'Scanner 01',
                'attendance_type' => 'Morning Check-in'
            ],
            [
                'student_id' => 'STU005',
                'student_name' => 'David Michael Kinyua',
                'level' => 'CSEE',
                'stream' => 'Stream B',
                'class' => 'Class 2D',
                'scan_time' => now()->setHours(7, 50, 10),
                'check_in_time' => '07:50 AM',
                'status' => 'late',
                'scan_method' => 'ID Card',
                'device' => 'Scanner 03',
                'attendance_type' => 'Morning Check-in'
            ]
        ];

        foreach ($attendanceRecords as $record) {
            \App\Models\StudentAttendanceRecord::create($record);
        }
    }
}

class SchoolEnquirySeeder extends Seeder
{
    public function run(): void
    {
        $enquiries = [
            [
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'email' => 'sarah.johnson@email.com',
                'phone' => '+255712345690',
                'level_interested' => 'QT',
                'source' => 'phone_call',
                'status' => 'new',
                'message' => 'Interested in enrolling my child for QT level',
                'follow_up_date' => now()->addDay(),
                'notes' => 'Parent called asking about admission requirements'
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Brown',
                'email' => 'michael.brown@email.com',
                'phone' => '+255712345691',
                'level_interested' => 'CSEE',
                'source' => 'walk_in',
                'status' => 'contacted',
                'message' => 'Visited school premises for information',
                'follow_up_date' => now()->addDay(),
                'notes' => 'Parent visited school, showed interest in CSEE program'
            ],
            [
                'first_name' => 'Grace',
                'last_name' => 'Williams',
                'email' => 'grace.williams@email.com',
                'phone' => '+255712345692',
                'level_interested' => 'ASCEE',
                'source' => 'whatsapp',
                'status' => 'followed_up',
                'message' => 'Need information about ASCEE curriculum',
                'follow_up_date' => now()->addDays(2),
                'notes' => 'Followed up via WhatsApp, sent curriculum details'
            ],
            [
                'first_name' => 'Robert',
                'last_name' => 'Davis',
                'email' => 'robert.davis@email.com',
                'phone' => '+255712345693',
                'level_interested' => 'English Course',
                'source' => 'facebook',
                'status' => 'converted',
                'message' => 'Saw Facebook ad about English course',
                'follow_up_date' => null,
                'notes' => 'Successfully enrolled in English Course program'
            ]
        ];

        foreach ($enquiries as $enquiry) {
            \App\Models\SchoolEnquiry::create($enquiry);
        }
    }
}
