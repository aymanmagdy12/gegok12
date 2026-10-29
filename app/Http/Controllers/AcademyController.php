<?php

namespace App\Http\Controllers;

use App\Services\AcademyProgression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AcademyController extends Controller
{
    private function actor(Request $request): object
    {
        $user = $request->user();
        abort_unless($user && $user->school_id && in_array((int) $user->usergroup_id, [3, 5, 6, 7], true), 403);
        return $user;
    }

    private function admin(Request $request): object
    {
        $user = $this->actor($request);
        abort_unless((int) $user->usergroup_id === 3, 403);
        return $user;
    }

    private function users(int $schoolId, int $role)
    {
        return DB::table('users')->where('school_id', $schoolId)
            ->where('usergroup_id', $role)->whereNull('deleted_at');
    }

    private function group(Request $request, int $id): object
    {
        $user = $this->actor($request);
        $group = DB::table('academy_groups')->where('school_id', $user->school_id)
            ->where('id', $id)->whereNull('archived_at')->first();
        abort_unless($group, 404);
        abort_unless((int) $user->usergroup_id === 3
            || ((int) $user->usergroup_id === 5 && (int) $group->teacher_id === (int) $user->id), 403);
        return $group;
    }

    public function index(Request $request)
    {
        $user = $this->actor($request);
        $role = (int) $user->usergroup_id;
        $groups = DB::table('academy_groups')->where('school_id', $user->school_id)->whereNull('archived_at');
        $people = collect();
        if ($role === 5) {
            $groups->where('teacher_id', $user->id);
        } elseif ($role === 6) {
            $people = $this->users($user->school_id, 6)->where('id', $user->id)->get();
        } elseif ($role === 7) {
            $children = DB::table('student_parent_links')->where('school_id', $user->school_id)
                ->where('parent_id', $user->id)->whereNull('deleted_at')->pluck('student_id');
            $people = $this->users($user->school_id, 6)->whereIn('id', $children)->get();
        }
        $counts = $role === 3 ? [
            'groups' => (clone $groups)->count(),
            'students' => $this->users($user->school_id, 6)->count(),
            'teachers' => $this->users($user->school_id, 5)->count(),
        ] : null;
        return view('academy.index', [
            'actor' => $user, 'counts' => $counts, 'people' => $people,
            'groups' => in_array($role, [3, 5], true) ? $groups->orderBy('level')->orderBy('name')->paginate(20) : null,
            'teachers' => $role === 3 ? $this->users($user->school_id, 5)->orderBy('name')->get(['id', 'name']) : collect(),
            'waiting' => $role === 3 ? DB::table('academy_enrollments as e')->join('users as u', 'u.id', '=', 'e.student_id')
                ->where('e.school_id', $user->school_id)->where('u.school_id', $user->school_id)
                ->whereNull('u.deleted_at')->whereNull('e.group_id')->whereNull('e.completed_at')
                ->orderBy('e.updated_at', 'desc')->limit(30)->get(['u.id', 'u.name', 'e.level']) : collect(),
        ]);
    }

    private function groupData(Request $request, int $schoolId): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'], 'level' => ['required', 'integer', 'between:1,6'],
            'schedule' => ['nullable', 'string', 'max:250'],
            'teacher_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($q) =>
                $q->where('school_id', $schoolId)->where('usergroup_id', 5)->whereNull('deleted_at'))],
        ]);
    }

    public function storeGroup(Request $request)
    {
        $user = $this->admin($request);
        $data = $this->groupData($request, $user->school_id);
        $id = DB::table('academy_groups')->insertGetId($data + [
            'school_id' => $user->school_id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return redirect()->route('academy.groups.show', $id)->with('status', 'تم إنشاء الجروب.');
    }

    public function showGroup(Request $request, int $group)
    {
        $record = $this->group($request, $group);
        $user = $this->actor($request);
        return view('academy.group', [
            'actor' => $user, 'group' => $record,
            'enrollments' => DB::table('academy_enrollments as e')->join('users as u', 'u.id', '=', 'e.student_id')
                ->where('e.school_id', $user->school_id)->where('u.school_id', $user->school_id)
                ->where('e.group_id', $group)->whereNull('u.deleted_at')
                ->orderBy('u.name')->paginate(30, ['e.*', 'u.name']),
            'teachers' => (int) $user->usergroup_id === 3
                ? $this->users($user->school_id, 5)->orderBy('name')->get(['id', 'name']) : collect(),
            'candidates' => (int) $user->usergroup_id === 3 ? $this->users($user->school_id, 6)
                ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
                ->orderBy('name')->limit(100)->get(['id', 'name']) : collect(),
        ]);
    }

    public function updateGroup(Request $request, int $group)
    {
        $user = $this->admin($request);
        $data = $this->groupData($request, $user->school_id);
        DB::transaction(function () use ($user, $group, $data) {
            $record = DB::table('academy_groups')->where('school_id', $user->school_id)
                ->where('id', $group)->whereNull('archived_at')->lockForUpdate()->first();
            abort_unless($record, 404);
            if ((int) $record->level !== (int) $data['level']
                && DB::table('academy_enrollments')->where('group_id', $group)->exists()) {
                throw ValidationException::withMessages(['level' => 'انقل الطلاب قبل تغيير مستوى الجروب.']);
            }
            DB::table('academy_groups')->where('id', $group)->update($data + ['updated_at' => now()]);
        });
        return back()->with('status', 'تم حفظ الجروب.');
    }

    public function archiveGroup(Request $request, int $group)
    {
        $user = $this->admin($request);
        DB::transaction(function () use ($user, $group) {
            $record = DB::table('academy_groups')->where('school_id', $user->school_id)
                ->where('id', $group)->whereNull('archived_at')->lockForUpdate()->first();
            abort_unless($record, 404);
            if (DB::table('academy_enrollments')->where('group_id', $group)->exists()) {
                throw ValidationException::withMessages(['group' => 'انقل الطلاب إلى جروب آخر قبل أرشفة هذا الجروب.']);
            }
            DB::table('academy_groups')->where('id', $group)->update(['archived_at' => now(), 'updated_at' => now()]);
        });
        return redirect()->route('academy.index')->with('status', 'تمت أرشفة الجروب مع الاحتفاظ بسجل الفيدباك.');
    }

    public function enroll(Request $request, int $group)
    {
        $user = $this->admin($request);
        $data = $request->validate(['student_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($q) =>
            $q->where('school_id', $user->school_id)->where('usergroup_id', 6)->whereNull('deleted_at'))]]);
        DB::transaction(function () use ($user, $group, $data) {
            // Serialize concurrent first enrollments for this student as well as transfers.
            DB::table('users')->where('id', $data['student_id'])->lockForUpdate()->first();
            $enrollment = DB::table('academy_enrollments')->where('school_id', $user->school_id)
                ->where('student_id', $data['student_id'])->lockForUpdate()->first();
            $record = DB::table('academy_groups')->where('id', $group)->where('school_id', $user->school_id)
                ->whereNull('archived_at')->lockForUpdate()->first();
            abort_unless($record, 404);
            if ($enrollment && ($enrollment->completed_at || (int) $enrollment->level !== (int) $record->level)) {
                throw ValidationException::withMessages(['student_id' => 'اختار جروبًا يطابق المستوى الحالي للطالب.']);
            }
            if ($enrollment) {
                DB::table('academy_enrollments')->where('id', $enrollment->id)->update(['group_id' => $group, 'updated_at' => now()]);
            } else {
                DB::table('academy_enrollments')->insert([
                    'school_id' => $user->school_id, 'student_id' => $data['student_id'],
                    'group_id' => $group, 'level' => $record->level, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
        return back()->with('status', 'تم ربط الطالب بالجروب.');
    }

    public function student(Request $request, int $student)
    {
        $user = $this->actor($request);
        $record = $this->users($user->school_id, 6)->where('id', $student)->first(['id', 'name']);
        abort_unless($record, 404);
        $enrollment = DB::table('academy_enrollments')->where('school_id', $user->school_id)->where('student_id', $student)->first();
        $role = (int) $user->usergroup_id;
        $allowed = $role === 3 || ($role === 6 && (int) $user->id === $student);
        if ($role === 7) {
            $allowed = DB::table('student_parent_links')->where('school_id', $user->school_id)
                ->where('parent_id', $user->id)->where('student_id', $student)->whereNull('deleted_at')->exists();
        } elseif ($role === 5 && $enrollment && $enrollment->group_id) {
            $allowed = DB::table('academy_groups')->where('id', $enrollment->group_id)->where('school_id', $user->school_id)
                ->where('teacher_id', $user->id)->whereNull('archived_at')->exists();
        }
        abort_unless($allowed, 403);
        return view('academy.student', [
            'actor' => $user, 'student' => $record, 'enrollment' => $enrollment,
            'feedback' => DB::table('academy_level_feedback')->where('school_id', $user->school_id)
                ->where('student_id', $student)->orderBy('level')->get(),
        ]);
    }

    public function units(Request $request, int $enrollment)
    {
        $user = $this->actor($request);
        abort_unless((int) $user->usergroup_id === 5, 403);
        $data = $request->validate(['level' => ['required', 'integer', 'between:1,6'],
            'unit_one_done' => ['required', 'boolean'], 'unit_two_done' => ['required', 'boolean']]);
        DB::transaction(function () use ($user, $enrollment, $data) {
            $record = DB::table('academy_enrollments')->where('school_id', $user->school_id)
                ->where('id', $enrollment)->lockForUpdate()->first();
            abort_unless($record, 404);
            $group = DB::table('academy_groups')->where('id', $record->group_id)->where('school_id', $user->school_id)
                ->whereNull('archived_at')->lockForUpdate()->first();
            abort_unless($group && (int) $group->teacher_id === (int) $user->id, 403);
            if ($record->completed_at || (int) $record->level !== (int) $data['level']) {
                throw ValidationException::withMessages(['level' => 'المستوى تغيّر؛ حدّث الصفحة.']);
            }
            DB::table('academy_enrollments')->where('id', $enrollment)->update([
                'unit_one_done' => (bool) $data['unit_one_done'], 'unit_two_done' => (bool) $data['unit_two_done'], 'updated_at' => now(),
            ]);
        });
        return back()->with('status', 'تم حفظ إتمام الوحدات.');
    }

    public function complete(Request $request, int $enrollment, AcademyProgression $progression)
    {
        $user = $this->actor($request);
        abort_unless((int) $user->usergroup_id === 5, 403);
        $data = $request->validate(['level' => ['required', 'integer', 'between:1,6'], 'feedback' => ['required', 'string', 'max:5000']]);
        $progression->complete((int) $user->school_id, (int) $user->id, $enrollment, (int) $data['level'], $data['feedback']);
        return back()->with('status', 'تم حفظ الفيدباك وإتمام المستوى، بدون نجاح أو رسوب.');
    }
}
