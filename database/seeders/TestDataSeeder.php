<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Program;
use App\Models\Room;
use App\Models\Subject;
use App\Models\User;
use App\Models\InstructorLoad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * TestDataSeeder
 * 
 * Comprehensive seeder for schedule generation testing.
 * Creates realistic test data with multiple programs, instructors, subjects, and rooms.
 * 
 * DATA STRUCTURE:
 * - 2 Departments
 * - 3 Programs per department (6 total)
 * - 3 Year Levels per program
 * - 2-3 Sections per year level
 * - 10-15 Subjects per program
 * - 8-12 Instructors per department with mixed contract types
 * - Different daily schemes (7AM-4PM, 8AM-5PM, 10AM-7PM)
 * - 8+ Rooms (mix of Lecture, Lab, Seminar)
 */
class TestDataSeeder extends Seeder
{
    private array $departments = [];
    private array $programs = [];
    private array $instructors = [];
    private array $rooms = [];
    private array $academicYears = [];

    public function run(): void
    {
        $this->command->info('Starting TestDataSeeder...');

        try {
            DB::beginTransaction();

            $this->seedAcademicYears();
            $this->seedDepartments();
            $this->seedPrograms();
            $this->seedInstructors();
            $this->seedRooms();
            $this->seedSubjects();
            $this->seedProgramSubjects();
            $this->seedInstructorLoads();

            DB::commit();
            $this->command->info('✓ TestDataSeeder completed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("✗ Seeding failed: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Seed academic years
     */
    private function seedAcademicYears(): void
    {
        $this->command->line('Seeding academic years...');

        $years = [
            ['start_year' => 2025, 'end_year' => 2026, 'is_active' => true],
            ['start_year' => 2026, 'end_year' => 2027, 'is_active' => false],
        ];

        foreach ($years as $year) {
            $academicYear = AcademicYear::firstOrCreate(
                ['start_year' => $year['start_year'], 'end_year' => $year['end_year']],
                $year
            );
            $this->academicYears[] = $academicYear;
        }

        $this->command->info("  → Created " . count($this->academicYears) . " academic years");
    }

    /**
     * Seed 2 test departments
     */
    private function seedDepartments(): void
    {
        $this->command->line('Seeding departments...');

        $departments = [
            [
                'department_code' => 'CICT',
                'department_name' => 'College of Information and Communication Technology',
            ],
            [
                'department_code' => 'COESE',
                'department_name' => 'College of Engineering and Science Education',
            ],
        ];

        foreach ($departments as $dept) {
            $department = Department::firstOrCreate(
                ['department_code' => $dept['department_code']],
                $dept
            );
            $this->departments[] = $department;
        }

        $this->command->info("  → Created " . count($this->departments) . " departments");
    }

    /**
     * Seed 3 programs per department
     */
    private function seedPrograms(): void
    {
        $this->command->line('Seeding programs...');

        $programData = [
            'CICT' => [
                ['program_code' => 'BSCS', 'program_name' => 'Bachelor of Science in Computer Science'],
                ['program_code' => 'BSIT', 'program_name' => 'Bachelor of Science in Information Technology'],
                ['program_code' => 'BSIS', 'program_name' => 'Bachelor of Science in Information Systems'],
            ],
            'COESE' => [
                ['program_code' => 'BSCE', 'program_name' => 'Bachelor of Science in Civil Engineering'],
                ['program_code' => 'BSEE', 'program_name' => 'Bachelor of Science in Electrical Engineering'],
                ['program_code' => 'BSME', 'program_name' => 'Bachelor of Science in Mechanical Engineering'],
            ],
        ];

        foreach ($this->departments as $department) {
            $code = $department->department_code;
            $programs = $programData[$code] ?? [];

            foreach ($programs as $prog) {
                $program = Program::firstOrCreate(
                    ['program_code' => $prog['program_code']],
                    array_merge($prog, ['department_id' => $department->id])
                );
                $this->programs[] = $program;
            }
        }

        $this->command->info("  → Created " . count($this->programs) . " programs");
    }

    /**
     * Seed instructors with mixed contract types and daily schemes
     */
    private function seedInstructors(): void
    {
        $this->command->line('Seeding instructors...');

        $dailySchemes = [
            ['start' => '07:00', 'end' => '16:00', 'label' => '7AM-4PM'],
            ['start' => '08:00', 'end' => '17:00', 'label' => '8AM-5PM'],
            ['start' => '10:00', 'end' => '19:00', 'label' => '10AM-7PM'],
        ];

        $contractTypes = [
            User::CONTRACT_PERMANENT,
            User::CONTRACT_CONTRACTUAL,
        ];

        $count = 0;

        foreach ($this->departments as $department) {
            // Create 10-12 instructors per department
            $numInstructors = rand(10, 12);

            for ($i = 1; $i <= $numInstructors; $i++) {
                $scheme = $dailySchemes[($i - 1) % count($dailySchemes)];
                $contractType = $contractTypes[($i - 1) % count($contractTypes)];

                $email = "instructor.{$department->id}.{$i}@test.local";
                
                $instructor = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'first_name' => "Instructor",
                        'last_name' => "{$department->department_code}-{$i}",
                        'role' => User::ROLE_INSTRUCTOR,
                        'department_id' => $department->id,
                        'program_id' => $department->programs()->first()?->id,
                        'is_active' => true,
                        'contract_type' => $contractType,
                        'password' => Hash::make('TempPassword123!'),
                        'email_verified_at' => now(),
                    ]
                );

                // Update with daily scheme times (required for schedule generation)
                $instructor->update([
                    'daily_scheme_start' => $scheme['start'],
                    'daily_scheme_end' => $scheme['end'],
                ]);

                $this->instructors[] = $instructor;
                $count++;
            }
        }

        $this->command->info("  → Created $count instructors with daily schemes");
    }

    /**
     * Seed 8-10 rooms with mixed types
     */
    private function seedRooms(): void
    {
        $this->command->line('Seeding rooms...');

        $rooms = [
            ['room_code' => 'LEC-101', 'room_name' => 'Lecture Hall 101', 'room_type' => 'Lecture'],
            ['room_code' => 'LEC-102', 'room_name' => 'Lecture Hall 102', 'room_type' => 'Lecture'],
            ['room_code' => 'LEC-103', 'room_name' => 'Lecture Hall 103', 'room_type' => 'Lecture'],
            ['room_code' => 'LAB-101', 'room_name' => 'Computer Lab 101', 'room_type' => 'Laboratory'],
            ['room_code' => 'LAB-102', 'room_name' => 'Computer Lab 102', 'room_type' => 'Laboratory'],
            ['room_code' => 'LAB-103', 'room_name' => 'Science Lab 103', 'room_type' => 'Laboratory'],
            ['room_code' => 'SEM-101', 'room_name' => 'Seminar Room 101', 'room_type' => 'Seminar Room'],
            ['room_code' => 'SEM-102', 'room_name' => 'Seminar Room 102', 'room_type' => 'Seminar Room'],
            ['room_code' => 'AUD-101', 'room_name' => 'Auditorium 101', 'room_type' => 'Auditorium'],
            ['room_code' => 'AUD-102', 'room_name' => 'Auditorium 102', 'room_type' => 'Auditorium'],
        ];

        foreach ($rooms as $room) {
            $r = Room::firstOrCreate(
                ['room_code' => $room['room_code']],
                $room
            );
            $this->rooms[] = $r;
        }

        $this->command->info("  → Created " . count($this->rooms) . " rooms");
    }

    /**
     * Seed 12-15 subjects per program
     */
    private function seedSubjects(): void
    {
        $this->command->line('Seeding subjects...');

        $subjectData = [
            'CICT' => $this->generateCSSubjects(),
            'COESE' => $this->generateEngineeringSubjects(),
        ];

        $count = 0;

        foreach ($this->departments as $department) {
            $deptCode = $department->department_code;
            $subjects = $subjectData[$deptCode] ?? [];

            foreach ($subjects as $subj) {
                $subject = Subject::firstOrCreate(
                    ['subject_code' => $subj['subject_code'], 'department_id' => $department->id],
                    array_merge($subj, ['department_id' => $department->id, 'is_active' => true])
                );
                $count++;
            }
        }

        $this->command->info("  → Created $count subjects");
    }

    /**
     * Generate CS/IT related subjects
     */
    private function generateCSSubjects(): array
    {
        return [
            ['subject_code' => 'CS101', 'subject_name' => 'Introduction to Programming', 'lecture_hours' => 3, 'lab_hours' => 2, 'units' => 4],
            ['subject_code' => 'CS102', 'subject_name' => 'Data Structures', 'lecture_hours' => 3, 'lab_hours' => 2, 'units' => 4],
            ['subject_code' => 'CS201', 'subject_name' => 'Database Management Systems', 'lecture_hours' => 3, 'lab_hours' => 2, 'units' => 4],
            ['subject_code' => 'CS202', 'subject_name' => 'Web Development', 'lecture_hours' => 2, 'lab_hours' => 3, 'units' => 4],
            ['subject_code' => 'CS301', 'subject_name' => 'Software Engineering', 'lecture_hours' => 3, 'lab_hours' => 1, 'units' => 3],
            ['subject_code' => 'CS302', 'subject_name' => 'System Design', 'lecture_hours' => 3, 'lab_hours' => 2, 'units' => 4],
            ['subject_code' => 'IT101', 'subject_name' => 'IT Fundamentals', 'lecture_hours' => 2, 'lab_hours' => 2, 'units' => 3],
            ['subject_code' => 'IT102', 'subject_name' => 'Network Administration', 'lecture_hours' => 2, 'lab_hours' => 3, 'units' => 4],
            ['subject_code' => 'IT201', 'subject_name' => 'Cybersecurity', 'lecture_hours' => 3, 'lab_hours' => 2, 'units' => 4],
            ['subject_code' => 'IT202', 'subject_name' => 'Cloud Computing', 'lecture_hours' => 3, 'lab_hours' => 1, 'units' => 3],
            ['subject_code' => 'IS101', 'subject_name' => 'Business Analysis', 'lecture_hours' => 3, 'lab_hours' => 0, 'units' => 3],
            ['subject_code' => 'IS102', 'subject_name' => 'Enterprise Systems', 'lecture_hours' => 3, 'lab_hours' => 2, 'units' => 4],
            ['subject_code' => 'IS201', 'subject_name' => 'Data Analytics', 'lecture_hours' => 2, 'lab_hours' => 3, 'units' => 4],
            ['subject_code' => 'MATH101', 'subject_name' => 'Discrete Mathematics', 'lecture_hours' => 3, 'lab_hours' => 0, 'units' => 3],
            ['subject_code' => 'MATH102', 'subject_name' => 'Linear Algebra', 'lecture_hours' => 3, 'lab_hours' => 0, 'units' => 3],
        ];
    }

    /**
     * Generate engineering subjects
     */
    private function generateEngineeringSubjects(): array
    {
        return [
            ['subject_code' => 'ENGR101', 'subject_name' => 'Engineering Fundamentals', 'lecture_hours' => 3, 'lab_hours' => 2, 'units' => 4],
            ['subject_code' => 'ENGR102', 'subject_name' => 'Engineering Graphics', 'lecture_hours' => 2, 'lab_hours' => 3, 'units' => 4],
            ['subject_code' => 'CE101', 'subject_name' => 'Surveying', 'lecture_hours' => 2, 'lab_hours' => 3, 'units' => 4],
            ['subject_code' => 'CE102', 'subject_name' => 'Structural Analysis', 'lecture_hours' => 3, 'lab_hours' => 2, 'units' => 4],
            ['subject_code' => 'EE101', 'subject_name' => 'Circuit Analysis', 'lecture_hours' => 3, 'lab_hours' => 3, 'units' => 5],
            ['subject_code' => 'EE102', 'subject_name' => 'Electromagnetics', 'lecture_hours' => 3, 'lab_hours' => 2, 'units' => 4],
            ['subject_code' => 'ME101', 'subject_name' => 'Mechanics', 'lecture_hours' => 3, 'lab_hours' => 2, 'units' => 4],
            ['subject_code' => 'ME102', 'subject_name' => 'Thermodynamics', 'lecture_hours' => 3, 'lab_hours' => 2, 'units' => 4],
            ['subject_code' => 'MATH201', 'subject_name' => 'Differential Equations', 'lecture_hours' => 3, 'lab_hours' => 0, 'units' => 3],
            ['subject_code' => 'MATH202', 'subject_name' => 'Advanced Calculus', 'lecture_hours' => 3, 'lab_hours' => 0, 'units' => 3],
            ['subject_code' => 'PHYS101', 'subject_name' => 'Physics I', 'lecture_hours' => 3, 'lab_hours' => 3, 'units' => 5],
            ['subject_code' => 'PHYS102', 'subject_name' => 'Physics II', 'lecture_hours' => 3, 'lab_hours' => 3, 'units' => 5],
            ['subject_code' => 'CHEM101', 'subject_name' => 'General Chemistry', 'lecture_hours' => 2, 'lab_hours' => 3, 'units' => 4],
            ['subject_code' => 'CHEM102', 'subject_name' => 'Organic Chemistry', 'lecture_hours' => 2, 'lab_hours' => 3, 'units' => 4],
            ['subject_code' => 'GEN101', 'subject_name' => 'Professional Ethics', 'lecture_hours' => 2, 'lab_hours' => 0, 'units' => 2],
        ];
    }

    /**
     * Assign subjects to programs with year level and semester
     */
    private function seedProgramSubjects(): void
    {
        $this->command->line('Seeding program-subject relationships...');

        foreach ($this->programs as $program) {
            $subjects = Subject::where('department_id', $program->department_id)->get();

            if ($subjects->isEmpty()) {
                continue;
            }

            // Assign 10-15 subjects to each program
            $selectedSubjects = $subjects->random(min(rand(10, 15), $subjects->count()));

            foreach ($selectedSubjects as $subject) {
                for ($yearLevel = 1; $yearLevel <= 2; $yearLevel++) {
                    for ($semester = 1; $semester <= 2; $semester++) {
                        $program->subjects()->attach($subject->id, [
                            'year_level' => $yearLevel,
                            'semester' => $semester,
                        ]);
                    }
                }
            }
        }

        $this->command->info("  → Assigned subjects to programs");
    }

    /**
     * Seed instructor loads for testing
     */
    private function seedInstructorLoads(): void
    {
        $this->command->line('Seeding instructor loads...');

        $count = 0;
        $instructorsCollection = collect($this->instructors);

        foreach ($this->programs as $program) {
            $subjects = $program->subjects;

            if ($subjects->isEmpty()) {
                continue;
            }

            // Assign 3-5 subjects per instructor per program
            $deptInstructors = $instructorsCollection->filter(
                fn($i) => $i->department_id === $program->department_id
            );

            if ($deptInstructors->isEmpty()) {
                continue;
            }

            $programInstructors = $deptInstructors->random(min(4, $deptInstructors->count()));

            foreach ($programInstructors as $instructor) {
                $assignedSubjects = $subjects->random(min(rand(3, 5), $subjects->count()));

                foreach ($assignedSubjects as $subject) {
                    foreach ($this->academicYears as $academicYear) {
                        for ($yearLevel = 1; $yearLevel <= 2; $yearLevel++) {
                            for ($semester = 1; $semester <= 2; $semester++) {
                                // Vary loads based on contract type
                                if ($instructor->contract_type === User::CONTRACT_PERMANENT) {
                                    $lecHours = rand(0, (int)$subject->lecture_hours);
                                    $labHours = rand(0, (int)$subject->lab_hours);
                                } else {
                                    $lecHours = (int)$subject->lecture_hours;
                                    $labHours = (int)$subject->lab_hours;
                                }

                                InstructorLoad::firstOrCreate(
                                    [
                                        'instructor_id' => $instructor->id,
                                        'program_id' => $program->id,
                                        'subject_id' => $subject->id,
                                        'academic_year_id' => $academicYear->id,
                                        'semester' => $semester,
                                        'year_level' => $yearLevel,
                                        'block_section' => "Block $yearLevel",
                                    ],
                                    [
                                        'lec_hours' => $lecHours,
                                        'lab_hours' => $labHours,
                                        'total_hours' => $lecHours + $labHours,
                                    ]
                                );

                                $count++;
                            }
                        }
                    }
                }
            }
        }

        $this->command->info("  → Created $count instructor loads");
    }
}
