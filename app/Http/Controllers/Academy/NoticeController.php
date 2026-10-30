<?php

namespace App\Http\Controllers\Academy;

use App\Http\Controllers\Controller;
use App\Services\AcademyNotices;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index(Request $request, AcademyNotices $notices)
    {
        $data = $request->validate(['q' => 'nullable|string|max:200']);
        $role = (int) $request->user()->usergroup_id;
        $rows = $notices->query($request->user(), trim($data['q'] ?? ''))->paginate(15)->withQueryString();
        return view('academy.notices', ['notices' => $rows, 'role' => $role, 'layout' => match ($role) {
            3 => 'layouts.admin.layout', 5 => 'layouts.teacher.layout', 6 => 'layouts.student.layout', 7 => 'academy.parent-layout', default => abort(403),
        }]);
    }
}
