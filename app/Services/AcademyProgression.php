<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademyProgression
{
    /** Teacher authorization is repeated under the same locks as progression. */
    public function complete(int $schoolId, int $teacherId, int $enrollmentId, int $expectedLevel, string $feedback): void
    {
        DB::transaction(function () use ($schoolId, $teacherId, $enrollmentId, $expectedLevel, $feedback) {
            $enrollment = DB::table('academy_enrollments')->where('school_id', $schoolId)
                ->where('id', $enrollmentId)->lockForUpdate()->first();
            abort_unless($enrollment, 404);
            $group = DB::table('academy_groups')->where('school_id', $schoolId)
                ->where('id', $enrollment->group_id)->lockForUpdate()->first();
            abort_unless($group && !$group->archived_at && (int) $group->teacher_id === $teacherId, 403);
            if ($enrollment->completed_at || (int) $enrollment->level !== $expectedLevel
                || (int) $group->level !== $expectedLevel) {
                throw ValidationException::withMessages(['progress' => 'تم تغيير مستوى الطالب بالفعل. حدّث الصفحة.']);
            }
            if (!$enrollment->unit_one_done || !$enrollment->unit_two_done || trim($feedback) === '') {
                throw ValidationException::withMessages(['progress' => 'أكمل الوحدتين واكتب فيدباك الطالب أولًا.']);
            }
            DB::table('academy_level_feedback')->insert([
                'school_id' => $schoolId, 'student_id' => $enrollment->student_id,
                'teacher_id' => $teacherId, 'group_id' => $group->id,
                'level' => $expectedLevel, 'feedback' => trim($feedback),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $final = $expectedLevel === 6;
            DB::table('academy_enrollments')->where('id', $enrollmentId)->update([
                'level' => $final ? 6 : $expectedLevel + 1,
                'group_id' => null,
                'unit_one_done' => $final, 'unit_two_done' => $final,
                'completed_at' => $final ? now() : null, 'updated_at' => now(),
            ]);
        });
    }
}
