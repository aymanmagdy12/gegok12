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
                ->leftJoin('academy_group_settings as s', 's.group_id', '=', 'g.id')->whereNull('g.deleted_at')
                ->whereIn('c.id', app(AcademyAssignments::class)->classIds($actor))
                ->when(config('academy_backend.test_only'), fn ($q) => $q->where('s.is_demo', 1))
                ->orderBy('g.group_name')->get(['g.id', 'g.group_name', 's.level', 's.meeting_days', 's.start_time', 's.end_time']);
            $groupStudents = app(AcademyLessons::class)->rosterPreview($actor, $groups->pluck('id')->all());
            $pendingLeave = app(AcademyTeacherWorkspace::class)->leaves($actor)->where('l.status', 'pending')->count();
        }
        $cards = collect();
        if ($role !== 5) {
            foreach ($students as $student) { $cards->push(['student' => $student, 'progress' => app(AcademyChecklists::class)->student($actor, (int) $student->id)]); }
        }
        return view('academy.dashboard', compact('role', 'students', 'groups', 'groupStudents', 'pendingLeave', 'cards', 'context') + [
            'layout' => match ($role) { 5 => 'layouts.teacher.layout', 6 => 'layouts.student.layout', 7 => 'academy.parent-layout' }]);
    }
}
