<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend the status enum to include GENERATED and FINALIZED which are used by the application.
        DB::statement("ALTER TABLE schedules MODIFY status ENUM('DRAFT','GENERATED','PENDING_APPROVAL','APPROVED','REJECTED','FINALIZED') DEFAULT 'DRAFT'");
    }

    public function down(): void
    {
        // Revert existing rows before removing values so MySQL doesn't fail.
        DB::table('schedules')->whereIn('status', ['GENERATED', 'FINALIZED'])->update(['status' => 'DRAFT']);
        DB::statement("ALTER TABLE schedules MODIFY status ENUM('DRAFT','PENDING_APPROVAL','APPROVED','REJECTED') DEFAULT 'DRAFT'");
    }
};
