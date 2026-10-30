<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('academy_checklist_cycles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('group_id'); $t->unsignedInteger('number');
            $t->boolean('active')->default(true); $t->unsignedInteger('created_by'); $t->timestamps();
            $t->unique(['group_id', 'number']); $t->index(['school_id', 'group_id', 'active']);
        });
        Schema::create('academy_checklist_students', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('cycle_id'); $t->unsignedInteger('student_id');
            $t->unsignedInteger('revision')->default(0); $t->timestamps();
            $t->unique(['cycle_id', 'student_id']); $t->index(['school_id', 'student_id', 'cycle_id']);
        });
        Schema::create('academy_checklist_marks', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('participant_id'); $t->unsignedTinyInteger('session');
            $t->boolean('attendance')->nullable(); $t->boolean('homework')->nullable(); $t->timestamps();
            $t->unique(['participant_id', 'session']);
        });
        Schema::create('academy_learning_messages', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedInteger('student_id');
            $t->unsignedInteger('sender_id'); $t->text('message'); $t->timestamps(); $t->index(['school_id', 'student_id', 'id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('academy_learning_messages'); Schema::dropIfExists('academy_checklist_marks');
        Schema::dropIfExists('academy_checklist_students'); Schema::dropIfExists('academy_checklist_cycles');
    }
};
