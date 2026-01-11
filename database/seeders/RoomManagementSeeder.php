<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\RoomType;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomManagementSeeder extends Seeder
{
    public function run()
    {
        // Create buildings
        $buildings = [
            [
                'building_code' => 'BLD-001',
                'building_name' => 'Academic Building A',
                'description' => 'Main academic building with classrooms and labs'
            ],
            [
                'building_code' => 'BLD-002',
                'building_name' => 'Academic Building B',
                'description' => 'Secondary academic building'
            ],
            [
                'building_code' => 'BLD-003',
                'building_name' => 'Science Complex',
                'description' => 'Specialized laboratories and research facilities'
            ],
            [
                'building_code' => 'BLD-004',
                'building_name' => 'Engineering Building',
                'description' => 'Engineering laboratories and workshops'
            ]
        ];

        foreach ($buildings as $building) {
            Building::firstOrCreate(['building_code' => $building['building_code']], $building);
        }

        // Create room types
        $roomTypes = [
            [
                'type_name' => 'Classroom',
                'description' => 'Standard classroom for lectures'
            ],
            [
                'type_name' => 'Laboratory',
                'description' => 'Science and computer laboratories'
            ],
            [
                'type_name' => 'Auditorium',
                'description' => 'Large auditorium for presentations'
            ],
            [
                'type_name' => 'Seminar Room',
                'description' => 'Small room for seminars and workshops'
            ],
            [
                'type_name' => 'Office',
                'description' => 'Administrative and faculty offices'
            ]
        ];

        foreach ($roomTypes as $type) {
            RoomType::firstOrCreate(['type_name' => $type['type_name']], $type);
        }

        // Create sample rooms
        $buildingA = Building::where('building_code', 'BLD-001')->first();
        $buildingB = Building::where('building_code', 'BLD-002')->first();
        $scienceComplex = Building::where('building_code', 'BLD-003')->first();

        $classroom = RoomType::where('type_name', 'Classroom')->first();
        $lab = RoomType::where('type_name', 'Laboratory')->first();
        $auditorium = RoomType::where('type_name', 'Auditorium')->first();

        if ($buildingA && $classroom) {
            Room::firstOrCreate(
                ['room_code' => 'A-101'],
                [
                    'room_name' => 'Lecture Hall 101',
                    'building_id' => $buildingA->id,
                    'room_type_id' => $classroom->id
                ]
            );

            Room::firstOrCreate(
                ['room_code' => 'A-102'],
                [
                    'room_name' => 'Lecture Hall 102',
                    'building_id' => $buildingA->id,
                    'room_type_id' => $classroom->id
                ]
            );
        }

        if ($scienceComplex && $lab) {
            Room::firstOrCreate(
                ['room_code' => 'SCI-201'],
                [
                    'room_name' => 'Physics Laboratory',
                    'building_id' => $scienceComplex->id,
                    'room_type_id' => $lab->id
                ]
            );

            Room::firstOrCreate(
                ['room_code' => 'SCI-202'],
                [
                    'room_name' => 'Chemistry Laboratory',
                    'building_id' => $scienceComplex->id,
                    'room_type_id' => $lab->id
                ]
            );
        }

        if ($buildingB && $auditorium) {
            Room::firstOrCreate(
                ['room_code' => 'B-AUDIT'],
                [
                    'room_name' => 'Main Auditorium',
                    'building_id' => $buildingB->id,
                    'room_type_id' => $auditorium->id
                ]
            );
        }

        $this->command->info('Room management data seeded successfully!');
    }
}
