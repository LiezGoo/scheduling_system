<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curricula', function (Blueprint $table) {
            $table->id();
            $table->string('curriculum_code')->unique();
            $table->string('curriculum_name');
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year'); // e.g., "2023-2024"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curricula');
    }
};
