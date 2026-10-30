<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Student;

use App\Http\Resources\Student\Notice as NoticeResource;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\StandardLink;
use App\Models\Teacherlink;
use App\Models\NoticeBoard;
use App\Helpers\SiteHelper;

class NoticeBoardController extends Controller
{

    public function list(Request $request)
    {
        $data = $request->validate(['search' => 'nullable|string|max:200', 'standardLink_id' => 'nullable|integer|min:1']);
        $ids = app(\App\Services\AcademyNotices::class)->query($request->user(), trim($data['search'] ?? ''), $request->boolean('showExpired'))->reorder()->select('id');
        $notices = NoticeBoard::whereIn('id', $ids);
        if (!empty($data['standardLink_id'])) { $notices->where('standardLink_id', $data['standardLink_id']); }
        return NoticeResource::collection($notices->orderByDesc('publish_date')->orderByDesc('id')->paginate(10));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    { 
        $query = \Request::getQueryString();

        return view('/student/noticeboard/index' ,['query' => $query]);
    }  
}
