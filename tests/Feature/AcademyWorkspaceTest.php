<?php

namespace Tests\Feature;

use App\Services\AcademyProgression;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AcademyWorkspaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)), 'session.driver' => 'array']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedInteger('usergroup_id');
            $t->string('name'); $t->timestamp('deleted_at')->nullable();
        });
        Schema::create('student_parent_links', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('parent_id');
            $t->unsignedBigInteger('student_id'); $t->timestamp('deleted_at')->nullable();
        });
        (require database_path('migrations/2026_10_29_220000_create_academy_progress_tables.php'))->up();
        foreach ([[1, 3], [2, 5], [3, 6], [4, 7], [5, 5]] as [$id, $role]) {
            DB::table('users')->insert(['id' => $id, 'school_id' => 10, 'usergroup_id' => $role, 'name' => 'Account '.$id]);
        }
        DB::table('users')->insert(['id' => 6, 'school_id' => 20, 'usergroup_id' => 6, 'name' => 'Other academy']);
        DB::table('academy_groups')->insert(['id' => 1, 'school_id' => 10, 'name' => 'Group A', 'teacher_id' => 2, 'level' => 1]);
        DB::table('academy_enrollments')->insert(['id' => 1, 'school_id' => 10, 'student_id' => 3,
            'group_id' => 1, 'level' => 1, 'unit_one_done' => true, 'unit_two_done' => true]);
    }

    private function account(int $id, int $role, int $school = 10): GenericUser
    {
        return new GenericUser(['id' => $id, 'school_id' => $school, 'usergroup_id' => $role, 'name' => 'Account '.$id]);
    }

    public function test_completion_saves_feedback_and_advances_without_a_grade(): void
    {
        (new AcademyProgression)->complete(10, 2, 1, 1, 'Improved confidence; keep practising.');
        $this->assertDatabaseHas('academy_enrollments', ['id' => 1, 'level' => 2, 'group_id' => null, 'unit_one_done' => 0]);
        $this->assertDatabaseHas('academy_level_feedback', ['student_id' => 3, 'level' => 1, 'teacher_id' => 2]);
    }

    public function test_level_six_completes_the_program_without_creating_level_seven(): void
    {
        DB::table('academy_groups')->where('id', 1)->update(['level' => 6]);
        DB::table('academy_enrollments')->where('id', 1)->update(['level' => 6]);
        (new AcademyProgression)->complete(10, 2, 1, 6, 'Completed the programme.');
        $record = DB::table('academy_enrollments')->first();
        $this->assertSame(6, (int) $record->level);
        $this->assertNotNull($record->completed_at);
    }

    public function test_missing_unit_blocks_completion_without_losing_progress(): void
    {
        DB::table('academy_enrollments')->where('id', 1)->update(['unit_two_done' => false]);
        try {
            (new AcademyProgression)->complete(10, 2, 1, 1, 'Feedback');
            $this->fail('An incomplete level must not advance.');
        } catch (ValidationException $e) {
            $this->assertDatabaseCount('academy_level_feedback', 0);
            $this->assertDatabaseHas('academy_enrollments', ['id' => 1, 'level' => 1, 'group_id' => 1]);
        }
    }

    public function test_blank_feedback_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new AcademyProgression)->complete(10, 2, 1, 1, '   ');
    }

    public function test_repeated_completion_cannot_skip_a_level(): void
    {
        $progress = new AcademyProgression;
        $progress->complete(10, 2, 1, 1, 'Feedback');
        try {
            $progress->complete(10, 2, 1, 1, 'Repeated click');
            $this->fail('The second request must be rejected.');
        } catch (HttpException|ValidationException $e) {
            $this->assertDatabaseCount('academy_level_feedback', 1);
            $this->assertDatabaseHas('academy_enrollments', ['id' => 1, 'level' => 2]);
        }
    }

    public function test_unassigned_teacher_cannot_complete_someone_elses_group(): void
    {
        $this->expectException(HttpException::class);
        (new AcademyProgression)->complete(10, 5, 1, 1, 'Not my student');
    }

    public function test_parent_can_only_read_linked_children_and_cannot_write_progress(): void
    {
        $this->actingAs($this->account(4, 7));
        $this->get('/academy/students/3')->assertForbidden();
        DB::table('student_parent_links')->insert(['school_id' => 10, 'parent_id' => 4, 'student_id' => 3]);
        $this->get('/academy/students/3')->assertOk();
        $this->patch('/academy/enrollments/1/units', ['level' => 1, 'unit_one_done' => 1, 'unit_two_done' => 1])->assertForbidden();
        $this->get('/academy/students/6')->assertNotFound();
    }

    public function test_other_academy_admin_cannot_read_or_modify_group(): void
    {
        $this->actingAs($this->account(9, 3, 20));
        $this->get('/academy/groups/1')->assertNotFound();
        $this->delete('/academy/groups/1')->assertNotFound();
    }

    public function test_group_with_students_cannot_be_archived(): void
    {
        $this->actingAs($this->account(1, 3))->delete('/academy/groups/1')->assertSessionHasErrors('group');
        $this->assertDatabaseHas('academy_groups', ['id' => 1, 'archived_at' => null]);
    }

    public function test_advanced_student_cannot_be_put_back_in_a_lower_level(): void
    {
        (new AcademyProgression)->complete(10, 2, 1, 1, 'Ready for level two');
        $this->actingAs($this->account(1, 3))->post('/academy/groups/1/students', ['student_id' => 3])
            ->assertSessionHasErrors('student_id');
        $this->assertDatabaseHas('academy_enrollments', ['id' => 1, 'level' => 2, 'group_id' => null]);
    }

    public function test_student_cannot_create_a_group_or_read_another_student(): void
    {
        DB::table('users')->insert(['id' => 7, 'school_id' => 10, 'usergroup_id' => 6, 'name' => 'Another student']);
        $this->actingAs($this->account(3, 6))->post('/academy/groups', ['name' => 'No', 'level' => 1])->assertForbidden();
        $this->get('/academy/students/7')->assertForbidden();
    }
}
