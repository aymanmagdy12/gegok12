<?php

namespace App\Http\Controllers\Academy;

use App\Http\Controllers\Controller;
use App\Services\AcademyChecklists;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    public function open(Request $request, int $group, AcademyChecklists $checklists)
    {
        $data = $request->validate(['next' => 'nullable|boolean', 'previous_cycle' => 'nullable|integer|min:1']);
        $cycle = $checklists->open($request->user(), $group, (bool) ($data['next'] ?? false), $data['previous_cycle'] ?? null);
        return redirect('/learning/academy/groups/'.$group.'?cycle='.$cycle.'#eight-sessions');
    }
    public function save(Request $request, int $group, int $cycle, int $student, AcademyChecklists $checklists)
    {
        $checklists->save($request->user(), $group, $cycle, $student, $request->all());
        return back()->with('successmessage', 'Eight-session attendance and homework saved. Student and parent dashboards are updated.');
    }
}
