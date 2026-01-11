<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Faculty Load Seeder
 *
 * Seeds sample data for Faculty Load Management testing.
 * Creates eligible instructors and assigns them to subjects with load constraints.
 */
class FacultyLoadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create eligible instructors
        $instructors = User::whereIn('role', [
            User::ROLE_INSTRUCTOR,
            User::ROLE_PROGRAM_HEAD,
            User::ROLE_DEPARTMENT_HEAD,
        ])->active()->limit(5)->get();

        if ($instructors->isEmpty()) {
            $this->command->warn('No active eligible instructors found. Create some first.');
            return;
        }

        // Get available subjects
        $subjects = Subject::limit(10)->get();

        if ($subjects->isEmpty()) {
            $this->command->warn('No subjects found. Create some first.');
            return;
        }

        // Assign random subjects to instructors with load constraints
        $assignments = 0;
        foreach ($instructors as $instructor) {
            // Each instructor gets 2-4 random subjects
            $numSubjects = rand(2, 4);
            $randomSubjects = $subjects->random(min($numSubjects, $subjects->count()));

            foreach ($randomSubjects as $subject) {
                // Check if assignment already exists
                $exists = DB::table('faculty_subjects')
                    ->where('user_id', $instructor->id)
                    ->where('subject_id', $subject->id)
                    ->exists();

                if (!$exists) {
                    $instructor->facultySubjects()->attach($subject->id, [
                        'max_sections' => rand(2, 4),
                        'max_load_units' => rand(9, 15),
                    ]);

                    $assignments++;
                }
            }
        }

        $this->command->info("Faculty Load seeding completed. Created {$assignments} assignments.");
    }
}
