<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassroomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classrooms = [
            [
                'name' => 'Primary Classroom A',
                'room_number' => 'PRM-001',
                'capacity' => 25,
                'building' => 'Primary Block',
                'floor' => 'Ground Floor',
                'classroom_type' => 'general',
                'status' => 'active',
                'description' => 'Primary level classroom for grades 1-2',
                'facilities' => json_encode(['whiteboard', 'projector', 'storage']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Primary Classroom B',
                'room_number' => 'PRM-002',
                'capacity' => 25,
                'building' => 'Primary Block',
                'floor' => 'Ground Floor',
                'classroom_type' => 'general',
                'status' => 'active',
                'description' => 'Primary level classroom for grades 3-4',
                'facilities' => json_encode(['whiteboard', 'projector', 'storage']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Science Laboratory',
                'room_number' => 'SCI-101',
                'capacity' => 30,
                'building' => 'Science Block',
                'floor' => 'First Floor',
                'classroom_type' => 'lab',
                'status' => 'active',
                'description' => 'Fully equipped science laboratory',
                'facilities' => json_encode(['lab_benches', 'safety_equipment', 'projector', 'sink', 'gas']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Computer Lab',
                'room_number' => 'CMP-201',
                'capacity' => 20,
                'building' => 'Technology Block',
                'floor' => 'Second Floor',
                'classroom_type' => 'computer',
                'status' => 'active',
                'description' => 'Computer laboratory with 20 workstations',
                'facilities' => json_encode(['computers', 'projector', 'internet', 'printer']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Art Room',
                'room_number' => 'ART-301',
                'capacity' => 15,
                'building' => 'Creative Arts Block',
                'floor' => 'Third Floor',
                'classroom_type' => 'art',
                'status' => 'active',
                'description' => 'Art and design classroom',
                'facilities' => json_encode(['easels', 'storage', 'sink', 'natural_light']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Music Room',
                'room_number' => 'MUS-401',
                'capacity' => 20,
                'building' => 'Creative Arts Block',
                'floor' => 'Fourth Floor',
                'classroom_type' => 'music',
                'status' => 'active',
                'description' => 'Music classroom with instruments',
                'facilities' => json_encode(['piano', 'instruments', 'sound_system', 'storage']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Lecture Hall A',
                'room_number' => 'LEC-101',
                'capacity' => 50,
                'building' => 'Main Block',
                'floor' => 'Ground Floor',
                'classroom_type' => 'lecture',
                'status' => 'active',
                'description' => 'Large lecture hall for presentations',
                'facilities' => json_encode(['projector', 'sound_system', 'microphone', 'whiteboard']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Secondary Classroom A',
                'room_number' => 'SEC-101',
                'capacity' => 35,
                'building' => 'Secondary Block',
                'floor' => 'First Floor',
                'classroom_type' => 'general',
                'status' => 'active',
                'description' => 'Secondary level classroom for grades 8-9',
                'facilities' => json_encode(['whiteboard', 'projector', 'storage']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Secondary Classroom B',
                'room_number' => 'SEC-102',
                'capacity' => 35,
                'building' => 'Secondary Block',
                'floor' => 'First Floor',
                'classroom_type' => 'general',
                'status' => 'active',
                'description' => 'Secondary level classroom for grades 10-11',
                'facilities' => json_encode(['whiteboard', 'projector', 'storage']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Conference Room',
                'room_number' => 'CONF-501',
                'capacity' => 15,
                'building' => 'Admin Block',
                'floor' => 'Fifth Floor',
                'classroom_type' => 'lecture',
                'status' => 'active',
                'description' => 'Conference room for meetings and small group sessions',
                'facilities' => json_encode(['projector', 'conference_phone', 'whiteboard', 'coffee_machine']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('classrooms')->insert($classrooms);
    }
}
