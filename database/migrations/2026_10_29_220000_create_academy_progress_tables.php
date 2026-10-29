<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('academy_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('name', 120);
            $table->unsignedTinyInteger('level');
            $table->unsignedBigInteger('teacher_id')->nullable()->index();
            $table->string('schedule', 250)->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
        Schema::create('academy_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('student_id');
            $table->foreignId('group_id')->nullable()->constrained('academy_groups');
            $table->unsignedTinyInteger('level')->default(1);
            $table->boolean('unit_one_done')->default(false);
            $table->boolean('unit_two_done')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'student_id']);
        });
        Schema::create('academy_level_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('teacher_id');
            $table->foreignId('group_id')->constrained('academy_groups');
            $table->unsignedTinyInteger('level');
            $table->text('feedback');
            $table->timestamps();
            $table->unique(['school_id', 'student_id', 'level'], 'academy_feedback_student_level_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_level_feedback');
        Schema::dropIfExists('academy_enrollments');
        Schema::dropIfExists('academy_groups');
    }
};
