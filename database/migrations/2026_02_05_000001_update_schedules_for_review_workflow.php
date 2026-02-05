<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('schedules', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('schedules', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (!Schema::hasColumn('schedules', 'review_remarks')) {
                $table->text('review_remarks')->nullable()->after('reviewed_at');
            }
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE schedules MODIFY status ENUM('DRAFT','PENDING_APPROVAL','APPROVED','REJECTED') DEFAULT 'DRAFT'");
        }

        DB::table('schedules')
            ->where('status', 'PENDING')
            ->update(['status' => 'PENDING_APPROVAL']);
    }

    public function down(): void
    {
        DB::table('schedules')
            ->where('status', 'PENDING_APPROVAL')
            ->update(['status' => 'PENDING']);

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE schedules MODIFY status ENUM('DRAFT','PENDING','APPROVED','REJECTED') DEFAULT 'DRAFT'");
        }

        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'review_remarks')) {
                $table->dropColumn('review_remarks');
            }
            if (Schema::hasColumn('schedules', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
            if (Schema::hasColumn('schedules', 'reviewed_by')) {
                $table->dropConstrainedForeignId('reviewed_by');
            }
        });
    }
};
