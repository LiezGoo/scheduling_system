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
                'building_code' => 'CCB',
                'building_name' => 'CCB Building',
                'description' => 'Lecture rooms in the CCB building',
            ],
            [
                'building_code' => 'ICT',
                'building_name' => 'ICT Building',
                'description' => 'Lecture and laboratory rooms for ICT classes',
            ],
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

        $ccbBuilding = Building::where('building_code', 'CCB')->first();
        $ictBuilding = Building::where('building_code', 'ICT')->first();

        $rooms = [];

        foreach (range(1, 6) as $number) {
            $rooms[] = [
                'room_code' => "CCB-{$number}",
                'room_name' => "CCB Room {$number}",
                'room_type' => 'lecture',
                'building_id' => $ccbBuilding?->id,
            ];
        }

        foreach (range(6, 10) as $number) {
            $rooms[] = [
                'room_code' => "ICT-{$number}",
                'room_name' => "ICT Room {$number}",
                'room_type' => 'lecture',
                'building_id' => $ictBuilding?->id,
            ];
        }

        foreach (range('A', 'D') as $suffix) {
            $rooms[] = [
                'room_code' => "LAB-{$suffix}",
                'room_name' => "LAB {$suffix}",
                'room_type' => 'laboratory',
                'building_id' => $ictBuilding?->id,
            ];
        }

        foreach ($rooms as $room) {
            Room::updateOrCreate(
                ['room_code' => $room['room_code']],
                [
                    'room_name' => $room['room_name'],
                    'room_type' => $room['room_type'],
                    'building_id' => $room['building_id'],
                ]
            );
        }

        $this->command->info('Room management data seeded successfully!');
    }
}
