<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add time fields for daily working scheme
            $table->time('daily_scheme_start')->nullable()->after('faculty_scheme');
            $table->time('daily_scheme_end')->nullable()->after('daily_scheme_start');
        });

        // Populate existing data based on faculty_scheme
        DB::table('users')->whereNotNull('faculty_scheme')->get()->each(function ($user) {
            $scheme = $user->faculty_scheme;
            $times = $this->parseFacultyScheme($scheme);
            
            if ($times) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'daily_scheme_start' => $times['start'],
                        'daily_scheme_end' => $times['end'],
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['daily_scheme_start', 'daily_scheme_end']);
        });
    }

    /**
     * Parse faculty scheme text to time values
     */
    private function parseFacultyScheme(string $scheme): ?array
    {
        $patterns = [
            '7:00 AM – 4:00 PM' => ['start' => '07:00:00', 'end' => '16:00:00'],
            '8:00 AM – 5:00 PM' => ['start' => '08:00:00', 'end' => '17:00:00'],
            '10:00 AM – 7:00 PM' => ['start' => '10:00:00', 'end' => '19:00:00'],
        ];

        return $patterns[$scheme] ?? null;
    }
};
