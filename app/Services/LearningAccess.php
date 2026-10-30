<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LearningAccess
{
    /** One academy boundary and one role policy for learning reads. */
    public function students(object $actor)
    {
        abort_unless($actor->school_id && in_array((int) $actor->usergroup_id, [3, 5, 6, 7], true), 403);
        $query = DB::table('users')->where('users.school_id', $actor->school_id)->where('users.usergroup_id', 6)->whereNull('users.deleted_at');
        return match ((int) $actor->usergroup_id) {
            3 => $query,
            6 => $query->where('users.id', $actor->id),
            7 => $query->whereIn('users.id', DB::table('student_parent_links')->select('student_id')
                ->where('school_id', $actor->school_id)->where('parent_id', $actor->id)->whereNull('deleted_at')),
            5 => $query->whereIn('users.id', DB::table('student_academics as a')->select('a.user_id')
                ->join('standards_link as g', 'g.id', '=', 'a.standardLink_id')
                ->where('a.school_id', $actor->school_id)->where('g.school_id', $actor->school_id)
                ->where('a.academic_year_id', app(AcademyContext::class)->period((int) $actor->school_id)->id)
                ->whereColumn('a.academic_year_id', 'g.academic_year_id')->where('g.status', 1)
                ->whereNull('a.deleted_at')->whereNull('g.deleted_at')
                ->where(function ($groups) use ($actor) {
                    $groups->where('g.class_teacher_id', $actor->id)->orWhereIn('g.id',
                        DB::table('class_teacher_links')->select('standardLink_id')->where('school_id', $actor->school_id)
                            ->where('teacher_id', $actor->id)->where('status', 1)->whereNull('deleted_at'));
                })),
        };
    }

    public function student(object $actor, int $id): object
    {
        $student = $this->students($actor)->where('id', $id)->first(['id', 'name']);
        abort_unless($student, 404);
        return $student;
    }
}
