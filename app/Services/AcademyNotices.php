<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AcademyNotices
{
    public function query(object $actor, string $search = '', bool $includeExpired = false)
    {
        $role = (int) $actor->usergroup_id;
        abort_unless($actor->school_id && in_array($role, [3, 5, 6, 7], true), 403);
        $period = app(AcademyContext::class)->period((int) $actor->school_id);
        $classes = $role === 5 ? app(AcademyAssignments::class)->classIds($actor) :
            DB::table('student_academics as enrollment')->select('enrollment.standardLink_id')
                ->join('standards_link as classroom', 'classroom.id', '=', 'enrollment.standardLink_id')
                ->whereIn('enrollment.user_id', app(LearningAccess::class)->students($actor)->select('users.id'))
                ->where('enrollment.school_id', $actor->school_id)->where('enrollment.academic_year_id', $period->id)
                ->where('classroom.school_id', $actor->school_id)->where('classroom.academic_year_id', $period->id)
                ->where('classroom.status', 1)->whereNull('classroom.deleted_at')->whereNull('enrollment.deleted_at');
        $q = DB::table('notice_board')->where('school_id', $actor->school_id)->where('academic_year_id', $period->id)->whereNull('deleted_at');
        if ($role !== 3) {
            $q->where(function ($audience) use ($role, $classes) {
                $audience->where(fn ($all) => $all->where('type', 'school')->whereNull('standardLink_id'))
                    ->orWhere(fn ($group) => $group->where('type', 'class')->whereIn('standardLink_id', $classes));
                if ($role === 5) { $audience->orWhere(fn ($teachers) => $teachers->where('type', 'teacher')->whereNull('standardLink_id')); }
            });
        }
        if (!$includeExpired || $role !== 3) {
            $now = now()->toDateTimeString();
            $q->where('status', 1)->where(fn ($date) => $date->whereNull('publish_date')->orWhere('publish_date', '<=', $now))
                ->where(fn ($date) => $date->whereNull('expire_date')->orWhere('expire_date', '>=', $now));
        }
        if ($search !== '') {
            $q->where(fn ($text) => $text->where('title', 'like', '%'.$search.'%')->orWhere('description', 'like', '%'.$search.'%'));
        }
        return $q->orderByDesc('publish_date')->orderByDesc('id');
    }
}
