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
        $role = (int) $actor->usergroup_id;
        $empty = ['students' => collect(), 'selected' => null, 'links' => null];
        // Teachers navigate groups; parents see all linked children in dashboard cards.
        // Neither role has an implicit, remembered or query-selected child in the sidebar.
        if (!in_array($role, [6, 7], true)) { return $empty; }
        $key = $actor->school_id.':'.$actor->id;
        $entries = $this->cache[$request] ?? [];
        if (isset($entries[$key])) { return $entries[$key]; }
        $students = app(LearningAccess::class)->students($actor)->leftJoin('userprofiles as p', function ($j) {
            $j->on('p.user_id', '=', 'users.id')->whereNull('p.deleted_at');
        })->orderBy('p.firstname')->get(['users.id', 'users.name', 'p.firstname', 'p.lastname']);
        $selected = $role === 6 ? $students->first() : null;
        $links = $selected ? DB::table('academy_student_journeys')->where('school_id', $actor->school_id)
            ->where('student_id', $selected->id)->first(['homework_drive_url', 'recordings_url', 'report_card_url']) : null;
        $entries[$key] = compact('students', 'selected', 'links');
        $this->cache[$request] = $entries;
        return $entries[$key];
    }
}
