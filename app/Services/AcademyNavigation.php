<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademyNavigation
{
    private \WeakMap $cache;
    public function __construct() { $this->cache = new \WeakMap; }
    public function context(object $actor, Request $request): array
    {
        if (!in_array((int) $actor->usergroup_id, [5, 6, 7], true)) { return ['students' => collect(), 'selected' => null, 'links' => null]; }
        $id = $request->route('student') ?? $request->query('student');
        abort_if($id !== null && (!is_numeric($id) || (int) $id < 1), 404);
        $key = $actor->school_id.':'.$actor->id.':'.($id ?? 'first').':'.($request->route('group') ?? 'all');
        $entries = $this->cache[$request] ?? [];
        if (isset($entries[$key])) { return $entries[$key]; }
        $students = app(LearningAccess::class)->students($actor)->leftJoin('userprofiles as p', function ($j) { $j->on('p.user_id', '=', 'users.id')->whereNull('p.deleted_at'); })
            ->orderBy('p.firstname')->get(['users.id', 'users.name', 'p.firstname', 'p.lastname']);
        if ((int) $actor->usergroup_id === 5 && $request->route('group')) {
            $ids = app(AcademyLessons::class)->roster($actor, (int) $request->route('group'))->get()->pluck('id');
            $students = $students->whereIn('id', $ids)->values();
            if ($id) { abort_unless($students->contains('id', (int) $id), 404); }
        }
        if ($id) { app(LearningAccess::class)->student($actor, (int) $id); }
        $selected = $id ? $students->firstWhere('id', (int) $id) : $students->first();
        $links = $selected ? DB::table('academy_student_journeys')->where('school_id', $actor->school_id)->where('student_id', $selected->id)->first(['homework_drive_url', 'recordings_url', 'report_card_url']) : null;
        $entries[$key] = compact('students', 'selected', 'links');
        $this->cache[$request] = $entries;
        return $entries[$key];
    }
}
