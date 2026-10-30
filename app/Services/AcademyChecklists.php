<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AcademyChecklists
{
    public function open(object $actor, int $group, bool $next = false, ?int $previous = null): int
    {
        abort_unless((int) $actor->usergroup_id === 5, 403);
        app(AcademyLessons::class)->group($actor, $group);
        return DB::transaction(function () use ($actor, $group, $next, $previous) {
            DB::table('groups')->where('id', $group)->lockForUpdate()->first();
            $roster = app(AcademyLessons::class)->roster($actor, $group)->get();
            if ($roster->isEmpty()) { throw ValidationException::withMessages(['group' => 'Add students to the group before opening its checklist.']); }
            $cycle = DB::table('academy_checklist_cycles')->where('school_id', $actor->school_id)->where('group_id', $group)->where('active', 1)->orderByDesc('number')->first();
            if ($next && (!$cycle || (int) $cycle->id !== (int) $previous)) { throw ValidationException::withMessages(['group' => 'The current set changed. Refresh before starting another set.']); }
            if ($cycle && $next) { DB::table('academy_checklist_cycles')->where('id', $cycle->id)->update(['active' => 0, 'updated_at' => now()]); $cycle = null; }
            if (!$cycle) {
                $number = (int) DB::table('academy_checklist_cycles')->where('group_id', $group)->max('number') + 1;
                $id = DB::table('academy_checklist_cycles')->insertGetId(['school_id' => $actor->school_id, 'group_id' => $group, 'number' => $number, 'created_by' => $actor->id, 'active' => 1, 'created_at' => now(), 'updated_at' => now()]);
            } else { $id = $cycle->id; }
            foreach ($roster as $student) {
                DB::table('academy_checklist_students')->insertOrIgnore(['school_id' => $actor->school_id, 'cycle_id' => $id, 'student_id' => $student->id, 'revision' => 0, 'created_at' => now(), 'updated_at' => now()]);
                $participant = DB::table('academy_checklist_students')->where('cycle_id', $id)->where('student_id', $student->id)->value('id');
                foreach (range(1, 8) as $session) { DB::table('academy_checklist_marks')->insertOrIgnore(['participant_id' => $participant, 'session' => $session, 'created_at' => now(), 'updated_at' => now()]); }
            }
            return (int) $id;
        });
    }

    public function group(object $actor, int $group, ?int $cycle = null): array
    {
        app(AcademyLessons::class)->group($actor, $group);
        $cycles = DB::table('academy_checklist_cycles')->where('school_id', $actor->school_id)->where('group_id', $group)->orderByDesc('number')->get();
        $selected = $cycle ? $cycles->firstWhere('id', $cycle) : $cycles->firstWhere('active', 1);
        if ($cycle) { abort_unless($selected, 404); }
        $participants = $selected ? DB::table('academy_checklist_students as p')->join('users as u', 'u.id', '=', 'p.student_id')
            ->leftJoin('userprofiles as profile', function ($j) { $j->on('profile.user_id', '=', 'u.id')->whereNull('profile.deleted_at'); })
            ->where('p.school_id', $actor->school_id)->where('p.cycle_id', $selected->id)->orderBy('profile.firstname')
            ->get(['p.*', 'profile.firstname', 'profile.lastname', 'u.name']) : collect();
        $marks = DB::table('academy_checklist_marks')->whereIn('participant_id', $participants->pluck('id'))->orderBy('session')->get()->groupBy('participant_id');
        return compact('cycles', 'selected', 'participants', 'marks');
    }

    public function save(object $actor, int $group, int $cycle, int $student, array $input): void
    {
        abort_unless((int) $actor->usergroup_id === 5, 403);
        app(AcademyLessons::class)->group($actor, $group);
        abort_unless(app(AcademyLessons::class)->roster($actor, $group)->where('u.id', $student)->exists(), 403);
        $data = Validator::make($input, ['revision' => 'required|integer|min:0', 'marks' => 'required|array|size:8',
            'marks.*.attendance' => ['present', 'nullable', Rule::in(['0', '1'])], 'marks.*.homework' => ['present', 'nullable', Rule::in(['0', '1'])]])->validate();
        if (array_map('intval', array_keys($data['marks'])) !== range(1, 8)) { throw ValidationException::withMessages(['marks' => 'Submit exactly sessions 1 through 8.']); }
        DB::transaction(function () use ($actor, $group, $cycle, $student, $data) {
            $c = DB::table('academy_checklist_cycles')->where('school_id', $actor->school_id)->where('group_id', $group)->where('id', $cycle)->lockForUpdate()->first();
            abort_unless($c, 404);
            if (!$c->active) { throw ValidationException::withMessages(['marks' => 'This set is archived. Open the current checklist.']); }
            $participant = DB::table('academy_checklist_students')->where('school_id', $actor->school_id)->where('cycle_id', $cycle)->where('student_id', $student)->lockForUpdate()->first();
            abort_unless($participant, 404);
            if ((int) $participant->revision !== (int) $data['revision']) { throw ValidationException::withMessages(['marks' => 'This checklist changed in another tab. Refresh before saving.']); }
            foreach ($data['marks'] as $session => $row) {
                $values = [];
                foreach (['attendance', 'homework'] as $field) { $value = $row[$field]; $values[$field] = $value === null || $value === '' ? null : (int) $value; }
                DB::table('academy_checklist_marks')->where('participant_id', $participant->id)->where('session', $session)->update($values + ['updated_at' => now()]);
            }
            DB::table('academy_checklist_students')->where('id', $participant->id)->update(['revision' => $participant->revision + 1, 'updated_at' => now()]);
            app(AcademyStudents::class)->log($actor, $student, 'eight_session_checklist_saved', ['group_id' => $group, 'cycle_id' => $cycle]);
        });
    }

    public function student(object $actor, int $student, ?int $cycle = null): array
    {
        app(LearningAccess::class)->student($actor, $student);
        $q = DB::table('academy_checklist_students as p')->join('academy_checklist_cycles as c', 'c.id', '=', 'p.cycle_id')
            ->join('groups as g', 'g.id', '=', 'c.group_id')->join('standards_link as class', 'class.id', '=', 'g.standardLink_id')
            ->where('c.school_id', $actor->school_id)->where('class.school_id', $actor->school_id)->where('p.school_id', $actor->school_id)->where('p.student_id', $student);
        if ($cycle) { $q->where('c.id', $cycle); }
        else {
            $q->where('c.active', 1)->whereNull('g.deleted_at')->whereExists(fn ($s) => $s->selectRaw('1')->from('group_members as gm')->whereColumn('gm.group_id', 'g.id')->where('gm.member_id', $student)->where('gm.member_type', 'student')->whereNull('gm.deleted_at'));
        }
        $participant = $q->orderByDesc('c.id')->first(['p.id', 'p.cycle_id', 'c.number', 'g.group_name']);
        if ($cycle) { abort_unless($participant, 404); }
        $marks = $participant ? DB::table('academy_checklist_marks')->where('participant_id', $participant->id)->orderBy('session')->get() : collect();
        $present = $marks->filter(fn ($m) => $m->attendance !== null && (int) $m->attendance === 1)->count();
        $done = $marks->filter(fn ($m) => $m->homework !== null && (int) $m->homework === 1)->count();
        $history = DB::table('academy_checklist_students as p')->join('academy_checklist_cycles as c', 'c.id', '=', 'p.cycle_id')
            ->join('groups as g', 'g.id', '=', 'c.group_id')->where('p.school_id', $actor->school_id)->where('c.school_id', $actor->school_id)
            ->where('p.student_id', $student)->orderByDesc('c.id')->get(['c.id', 'c.number', 'g.group_name']);
        return ['student_id' => $student, 'history' => $history, 'participant' => $participant, 'marks' => $marks, 'present' => $present, 'homework_done' => $done,
            'absent' => $marks->filter(fn ($m) => $m->attendance !== null && (int) $m->attendance === 0)->count(),
            'attendance_pending' => 8 - $marks->whereNotNull('attendance')->count(), 'homework_pending' => 8 - $marks->whereNotNull('homework')->count(),
            'attendance_percent' => $present * 100 / 8, 'homework_percent' => $done * 100 / 8];
    }
}
