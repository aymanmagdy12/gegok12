<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AcademyDashboard
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (config('academy.enabled') && $request->isMethod('GET')
            && in_array($request->path(), ['admin/dashboard', 'teacher/dashboard', 'student/dashboard'], true)
            && $user && $user->school_id && in_array((int) $user->usergroup_id, [3, 5, 6, 7], true)
            && Schema::hasTable('academy_groups')) {
            return redirect()->route('academy.index');
        }
        return $next($request);
    }
}
