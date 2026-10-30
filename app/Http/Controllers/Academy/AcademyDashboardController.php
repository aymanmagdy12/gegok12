<?php

namespace App\Http\Controllers\Academy;

use App\Http\Controllers\Controller;
use App\Services\AcademyAssignments;
use App\Services\AcademyChecklists;
use App\Services\AcademyLessons;
use App\Services\AcademyNavigation;
use App\Services\AcademyTeacherWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademyDashboardController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user(); $role = (int) $actor->usergroup_id;
        if ($role === 3) { return redirect('/learning/academy'); }
        abort_unless(in_array($role, [5, 6, 7], true), 403);
        $context = app(AcademyNavigation::class)->context($actor, $request);
        $students = $context['students']; $groups = collect(); $groupStudents = collect(); $pendingLeave = 0;
        if ($role === 5) {
            $groups = DB::table('groups as g')->join('standards_link as c', 'c.id', '=', 'g.standardLink_id')
                ->leftJoin('academy_group_settings as s', 's.group_id', '=', 'g.id')->whereNull('g.deleted_at')->where('g.status', 1)
                ->whereIn('c.id', app(AcademyAssignments::class)->classIds($actor))
                ->when(config('academy_backend.test_only'), fn ($q) => $q->where('s.is_demo', 1))
                ->orderBy('g.group_name')->get(['g.id', 'g.group_name', 's.level', 's.meeting_days', 's.start_time', 's.end_time', 's.timezone']);
            $groupStudents = app(AcademyLessons::class)->rosterPreview($actor, $groups->pluck('id')->all());
            $students = $groupStudents->flatten(1)->unique('id')->values();
            $pendingLeave = app(AcademyTeacherWorkspace::class)->leaves($actor)->where('l.status', 'pending')->count();
        }
        $cards = collect();
        if ($role !== 5) {
            $journeys = DB::table('academy_student_journeys')->where('school_id', $actor->school_id)->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');
            $memberships = DB::table('group_members as m')->join('groups as g', 'g.id', '=', 'm.group_id')
                ->join('standards_link as c', 'c.id', '=', 'g.standardLink_id')
                ->leftJoin('academy_group_settings as s', 's.group_id', '=', 'g.id')
                ->leftJoin('userprofiles as t', function ($j) { $j->on('t.user_id', '=', 'c.class_teacher_id')->whereNull('t.deleted_at'); })
                ->whereIn('m.member_id', $students->pluck('id'))->where('m.member_type', 'student')->whereNull('m.deleted_at')
                ->where('c.school_id', $actor->school_id)->where('c.status', 1)->whereNull('c.deleted_at')->whereNull('g.deleted_at')->where('g.status', 1)
                ->where('c.academic_year_id', app(\App\Services\AcademyContext::class)->period((int) $actor->school_id)->id)
                ->whereExists(fn ($q) => $q->selectRaw('1')->from('student_academics as a')->whereColumn('a.user_id', 'm.member_id')
                    ->whereColumn('a.standardLink_id', 'c.id')->whereColumn('a.academic_year_id', 'c.academic_year_id')->where('a.school_id', $actor->school_id)->whereNull('a.deleted_at'))
                ->get(['m.member_id', 'g.group_name', 's.meeting_days', 's.start_time', 's.end_time', 's.timezone', 't.firstname as teacher_name'])->groupBy('member_id');
            foreach ($students as $student) {
                $cards->push(['student' => $student, 'journey' => $journeys->get($student->id), 'groups' => $memberships->get($student->id, collect()),
                    'progress' => app(AcademyChecklists::class)->student($actor, (int) $student->id)]);
            }
        }
        return view('academy.dashboard', compact('role', 'students', 'groups', 'groupStudents', 'pendingLeave', 'cards', 'context') + [
            'layout' => match ($role) { 5 => 'layouts.teacher.layout', 6 => 'layouts.student.layout', 7 => 'academy.parent-layout' }]);
    }
}
