<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{Department, Program, Subject, User, Room};

echo "=== DIAGNOSTIC CHECK ===\n\n";

$departmentCount = Department::count();
echo "Departments: $departmentCount\n";

$programCount = Program::count();
echo "Programs: $programCount\n";

$subjectCount = Subject::count();
echo "Subjects total: $subjectCount\n";

$userCount = User::where('role', 'instructor')->count();
echo "Instructors: $userCount\n";

$roomCount = Room::count();
echo "Rooms: $roomCount\n";

// Check program-subject relationships
if ($programCount > 0) {
    $program = Program::first();
    $programSubjects = $program->subjects()->count();
    echo "\nProgram subjects for first program: $programSubjects\n";
    
    if ($programSubjects > 0) {
        $subj = $program->subjects()->first();
        echo "  First subject: " . $subj->subject_name . "\n";
    }
}

// Check if instructors have schemes
$withScheme = User::where('role', 'instructor')
    ->whereNotNull('daily_scheme_start')
    ->whereNotNull('daily_scheme_end')
    ->count();
echo "Instructors with daily schemes: $withScheme\n";

// Check active subjects
$activeSubjects = Subject::where('is_active', true)->count();
echo "Active subjects: $activeSubjects\n";

echo "\n=== END DIAGNOSTIC ===\n";
