<?php

namespace App\Http\Controllers\Academy;

use App\Http\Controllers\Controller;
use App\Services\AcademyStudents;
use App\Services\LearningAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademyMessageController extends Controller
{
    public function index(Request $request, LearningAccess $access, ?int $student = null)
    {
        $actor = $request->user(); $role = (int) $actor->usergroup_id;
        $students = $access->students($actor)->leftJoin('userprofiles as p', function ($j) { $j->on('p.user_id', '=', 'users.id')->whereNull('p.deleted_at'); })
            ->orderBy('p.firstname')->paginate(25, ['users.id', 'users.name', 'p.firstname', 'p.lastname'], 'students_page');
        $person = $student ? $access->student($actor, $student) : null; $messages = null;
        if ($person) {
            $profile = DB::table('userprofiles')->where('school_id', $actor->school_id)->where('user_id', $student)->whereNull('deleted_at')->first(['firstname', 'lastname']);
            $person->display_name = trim(($profile->firstname ?? '').' '.($profile->lastname ?? '')) ?: $person->name;
            $current = DB::table('academy_learning_messages as m')->join('users as u', 'u.id', '=', 'm.sender_id')
                ->leftJoin('userprofiles as p', function ($j) { $j->on('p.user_id', '=', 'u.id')->whereNull('p.deleted_at'); })
                ->where('m.school_id', $actor->school_id)->where('m.student_id', $student)
                ->select('m.id', 'm.message', 'm.created_at', 'u.usergroup_id as sender_role', 'p.firstname', 'p.lastname')->selectRaw("'message' as origin");
            $feedback = DB::table('academy_student_messages as m')->join('users as u', 'u.id', '=', 'm.teacher_id')
                ->leftJoin('userprofiles as p', function ($j) { $j->on('p.user_id', '=', 'u.id')->whereNull('p.deleted_at'); })
                ->where('m.school_id', $actor->school_id)->where('m.student_id', $student)
                ->select('m.id', 'm.message', 'm.created_at', 'u.usergroup_id as sender_role', 'p.firstname', 'p.lastname')->selectRaw("'feedback' as origin");
            $messages = DB::query()->fromSub($current->unionAll($feedback), 'thread')->orderByDesc('created_at')->orderByDesc('id')->orderBy('origin')->paginate(30, ['*'], 'messages_page');
        }
        return view('academy.messages-thread', compact('students', 'person', 'messages', 'role') + ['layout' => match ($role) {
            3 => 'layouts.admin.layout', 5 => 'layouts.teacher.layout', 6 => 'layouts.student.layout', 7 => 'academy.parent-layout', default => abort(403),
        }]);
    }
    public function send(Request $request, int $student, LearningAccess $access)
    {
        $actor = $request->user(); $access->student($actor, $student);
        $data = $request->validate(['message' => 'required|string|min:1|max:5000']);
        DB::transaction(function () use ($actor, $student, $data) {
            $id = DB::table('academy_learning_messages')->insertGetId(['school_id' => $actor->school_id, 'student_id' => $student,
                'sender_id' => $actor->id, 'message' => $data['message'], 'created_at' => now(), 'updated_at' => now()]);
            app(AcademyStudents::class)->log($actor, $student, 'learning_message_sent', ['message_id' => $id]);
        });
        return redirect('/learning/academy/messages/'.$student)->with('successmessage', 'Message sent.');
    }
}
