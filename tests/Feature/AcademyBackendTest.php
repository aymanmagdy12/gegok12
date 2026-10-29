<?php

namespace Tests\Feature;

use App\Auth\AcademyUserProvider;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Middleware\AcademyBackend;
use App\Models\Group;
use App\Models\Setting;
use App\Services\AcademyContext;
use App\Services\AcademyGroups;
use App\Services\ApplicationSettings;
use App\Services\LearningAccess;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AcademyBackendTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array', 'session.driver' => 'array', 'gexam.enabled' => false,
            'academy_backend.enabled' => true, 'app.key' => 'base64:'.base64_encode(str_repeat('b', 32))]);
        DB::purge('sqlite'); Cache::flush(); app()->forgetInstance(AcademyContext::class);
        Schema::create('schools', function (Blueprint $t) { $t->id(); $t->string('name'); $t->boolean('status')->default(1); $t->softDeletes(); });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_id'); $t->integer('usergroup_id'); $t->string('name');
            $t->string('email')->nullable(); $t->string('status')->default('active'); $t->string('password')->nullable();
            $t->unsignedBigInteger('ref_id')->nullable(); $t->string('registration_number')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('userprofiles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('user_id'); $t->integer('usergroup_id');
            $t->string('firstname')->nullable(); $t->string('lastname')->nullable(); $t->string('avatar')->nullable();
            $t->string('status')->default('active'); $t->date('date_of_birth')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('academic_years', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_id'); $t->string('name'); $t->text('description')->nullable();
            $t->dateTime('start_date'); $t->dateTime('end_date'); $t->boolean('status'); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_id'); $t->string('name'); $t->string('slug')->unique();
            $t->integer('order')->nullable(); $t->boolean('status')->default(1); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('school_id'); $t->string('name'); $t->boolean('status')->default(1); $t->softDeletes(); $t->timestamps(); });
        Schema::create('standards_link', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_id'); $t->integer('academic_year_id'); $t->integer('class_teacher_id');
            $t->integer('standard_id'); $t->integer('section_id'); $t->boolean('status')->default(1); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_id'); $t->integer('academic_year_id'); $t->integer('standard_id');
            $t->integer('section_id'); $t->string('name'); $t->string('type'); $t->boolean('status')->default(1); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('class_teacher_links', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_id'); $t->integer('academic_year_id'); $t->integer('standardLink_id');
            $t->integer('subject_id'); $t->integer('teacher_id'); $t->boolean('status')->default(1); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('groups', function (Blueprint $t) { $t->id(); $t->integer('standardLink_id'); $t->string('group_name'); $t->string('type')->default('class'); $t->boolean('status')->default(1); $t->softDeletes(); $t->timestamps(); });
        Schema::create('group_members', function (Blueprint $t) { $t->id(); $t->integer('group_id'); $t->integer('member_id'); $t->string('member_type'); $t->softDeletes(); $t->timestamps(); });
        Schema::create('student_academics', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('school_id'); $t->integer('academic_year_id'); $t->integer('user_id'); $t->integer('standardLink_id')->nullable(); $t->softDeletes(); $t->timestamps(); });
        Schema::create('student_parent_links', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('school_id'); $t->integer('student_id'); $t->integer('parent_id'); $t->softDeletes(); });
        Schema::create('settings', function (Blueprint $t) { $t->id(); $t->string('key'); $t->string('value'); $t->timestamps(); });
        Schema::create('homeworks', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_id'); $t->integer('academic_year_id'); $t->integer('standardLink_id');
            $t->integer('subject_id'); $t->string('description'); $t->date('date'); $t->softDeletes();
        });
        Schema::create('homework_approvals', function (Blueprint $t) { $t->id(); $t->integer('homework_id'); $t->string('status'); $t->softDeletes(); });
        Schema::create('attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_id'); $t->integer('academic_year_id'); $t->integer('user_id');
            $t->date('date'); $t->string('session'); $t->integer('status'); $t->softDeletes();
        });
        foreach ([10, 20, 30] as $id) { DB::table('schools')->insert(['id' => $id, 'name' => 'Academy '.$id]); }
        foreach ([[1,3,10], [2,5,10], [3,6,10], [4,7,10], [5,5,10], [6,6,20], [7,6,10]] as [$id,$role,$school]) {
            DB::table('users')->insert(['id' => $id, 'school_id' => $school, 'usergroup_id' => $role, 'name' => 'Account '.$id, 'email' => 'user'.$id.'@example.test']);
            DB::table('userprofiles')->insert(['school_id' => $school, 'user_id' => $id, 'usergroup_id' => $role, 'firstname' => 'Account', 'lastname' => (string) $id]);
        }
        foreach ([10,20] as $school) { DB::table('academic_years')->insert(['id' => $school, 'school_id' => $school, 'name' => 'Existing period', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1]); }
        DB::table('standards')->insert(['id' => 1, 'school_id' => 10, 'name' => 'Existing level', 'slug' => 'existing-level', 'order' => 1]);
        DB::table('sections')->insert(['id' => 1, 'school_id' => 10, 'name' => 'Original group']);
        DB::table('standards_link')->insert(['id' => 1, 'school_id' => 10, 'academic_year_id' => 10, 'class_teacher_id' => 2, 'standard_id' => 1, 'section_id' => 1]);
        DB::table('groups')->insert(['id' => 1, 'standardLink_id' => 1, 'group_name' => 'Original group']);
        DB::table('student_academics')->insert(['id' => 1, 'school_id' => 10, 'academic_year_id' => 10, 'user_id' => 3, 'standardLink_id' => 1]);
        DB::table('group_members')->insert(['group_id' => 1, 'member_id' => 3, 'member_type' => 'student']);
        DB::table('student_parent_links')->insert(['school_id' => 10, 'student_id' => 3, 'parent_id' => 4]);
    }

    private function actor(int $id, int $role, int $school = 10): GenericUser
    {
        return new GenericUser(['id' => $id, 'usergroup_id' => $role, 'school_id' => $school]);
    }

    public function test_fresh_academy_gets_one_context_and_six_levels_without_school_setup(): void
    {
        $context = app(AcademyContext::class);
        $period = $context->prepare(30);
        $this->assertSame($period->id, $context->prepare(30)->id);
        $this->assertDatabaseCount('academic_years', 3);
        $this->assertSame(6, DB::table('standards')->where('school_id', 30)->count());
    }

    public function test_existing_records_and_current_period_are_not_renamed_or_selected_globally(): void
    {
        Cache::put('academic_year', 20);
        $this->assertSame(10, (int) \App\Helpers\SiteHelper::getAcademicYear(10)->id);
        app(AcademyContext::class)->prepare(10);
        $this->assertDatabaseHas('standards', ['id' => 1, 'name' => 'Existing level']);
        $this->assertSame(1, DB::table('standards')->where('school_id', 10)->count());
    }

    public function test_direct_group_creation_adds_two_units_and_teacher_links_to_existing_learning_tables(): void
    {
        $group = (new AcademyGroups)->create(10, ['group_name' => 'Tuesday English', 'level' => 2, 'teacher_id' => 2]);
        $link = DB::table('standards_link')->where('id', $group->standardLink_id)->first();
        $this->assertSame(2, (int) $link->class_teacher_id);
        $this->assertSame(['Unit 1', 'Unit 2'], DB::table('subjects')->where('section_id', $link->section_id)->orderBy('name')->pluck('name')->all());
        $this->assertSame(2, DB::table('class_teacher_links')->where('standardLink_id', $link->id)->count());
        $this->assertSame('Level 2', DB::table('standards')->where('id', $link->standard_id)->value('name'));
    }

    public function test_legacy_group_payload_and_json_fields_still_work(): void
    {
        (new AcademyGroups)->create(10, ['group_name' => 'Practice', 'standards_link_id' => 1]);
        Auth::setUser($this->actor(1, 3));
        $json = (new GroupController)->index(1)->getData(true);
        $this->assertTrue($json['success']);
        $this->assertCount(2, $json['data']);
        $this->assertSame(['id','group_name','created_at'], array_keys($json['data'][0]));
    }

    public function test_group_members_relationship_uses_group_id_not_member_primary_key(): void
    {
        DB::table('group_members')->insert(['id' => 99, 'group_id' => 1, 'member_id' => 7, 'member_type' => 'student']);
        $this->assertSame(2, Group::findOrFail(1)->members()->count());
    }

    public function test_enrollment_is_idempotent_and_updates_learning_context_preserving_old_rows(): void
    {
        $service = new AcademyGroups;
        $group = $service->create(10, ['group_name' => 'Next group', 'level' => 2, 'teacher_id' => 2]);
        $service->enroll(10, $group->id, [3]);
        $service->enroll(10, $group->id, [3]);
        $this->assertSame(1, DB::table('group_members')->where('group_id', $group->id)->where('member_id', 3)->whereNull('deleted_at')->count());
        $this->assertDatabaseHas('student_academics', ['user_id' => 3, 'standardLink_id' => $group->standardLink_id, 'deleted_at' => null]);
        $this->assertNotNull(DB::table('student_academics')->where('id', 1)->value('deleted_at'));
        $this->assertDatabaseCount('student_academics', 2);
    }

    public function test_foreign_student_rejection_rolls_back_the_entire_batch(): void
    {
        try {
            (new AcademyGroups)->enroll(10, 1, [7, 6]);
            $this->fail('Cross-academy enrollment must fail.');
        } catch (ValidationException $e) {
            $this->assertDatabaseCount('group_members', 1);
            $this->assertDatabaseCount('student_academics', 1);
        }
    }

    public function test_group_lists_are_scoped_to_the_current_academy(): void
    {
        Auth::setUser($this->actor(9, 3, 20));
        $this->assertSame([], (new GroupController)->list()->getData(true)['data']);
    }

    public function test_parents_see_only_linked_children_and_students_only_themselves(): void
    {
        $access = new LearningAccess;
        $this->assertSame([3], $access->students($this->actor(4, 7))->pluck('id')->all());
        $this->assertSame([3], $access->students($this->actor(3, 6))->pluck('id')->all());
        $this->expectException(HttpException::class);
        $access->student($this->actor(4, 7), 7);
    }

    public function test_teachers_read_only_their_assigned_students(): void
    {
        $access = new LearningAccess;
        $this->assertSame([3], $access->students($this->actor(2, 5))->pluck('id')->all());
        $this->assertSame([], $access->students($this->actor(5, 5))->pluck('id')->all());
        $this->expectException(HttpException::class);
        $access->student($this->actor(2, 5), 6);
    }

    public function test_parent_homework_api_returns_only_approved_work_for_the_linked_child(): void
    {
        foreach ([1,2] as $id) { DB::table('homeworks')->insert(['id' => $id, 'school_id' => 10, 'academic_year_id' => 10, 'standardLink_id' => 1, 'subject_id' => 1, 'description' => 'Work '.$id, 'date' => '2026-10-30']); }
        DB::table('homework_approvals')->insert(['homework_id' => 1, 'status' => 'approved']);
        DB::table('homework_approvals')->insert(['homework_id' => 2, 'status' => 'pending']);
        $this->actingAs($this->actor(4, 7))->getJson('/learning/students/3/homework')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', 1);
        $this->getJson('/learning/students/6/homework')->assertNotFound();
    }

    public function test_settings_are_cached_and_invalidated_on_update(): void
    {
        Setting::create(['key' => 'login_status', 'value' => '1']);
        $service = new ApplicationSettings;
        $this->assertSame('1', $service->values()['login_status']);
        DB::enableQueryLog(); DB::flushQueryLog();
        $service->values(); $service->values();
        $this->assertCount(0, DB::getQueryLog());
        Setting::first()->update(['value' => '0']);
        $this->assertSame('0', $service->values()['login_status']);
    }

    public function test_auth_provider_loads_profile_without_loading_family_relations(): void
    {
        DB::enableQueryLog(); DB::flushQueryLog();
        $user = (new AcademyUserProvider(app('hash'), \App\Models\User::class))->retrieveById(3);
        $this->assertTrue($user->relationLoaded('userprofile'));
        $this->assertFalse($user->relationLoaded('members'));
        $this->assertFalse($user->relationLoaded('children'));
        $this->assertFalse($user->relationLoaded('parents'));
        $this->assertLessThanOrEqual(2, count(DB::getQueryLog()));
    }

    public function test_student_list_serialization_has_a_bounded_query_count_and_preserves_fields(): void
    {
        foreach (range(100, 119) as $id) {
            DB::table('users')->insert(['id' => $id, 'school_id' => 10, 'usergroup_id' => 6, 'name' => 'Student '.$id]);
            DB::table('userprofiles')->insert(['school_id' => 10, 'user_id' => $id, 'usergroup_id' => 6, 'firstname' => 'Student', 'lastname' => (string) $id]);
            DB::table('student_academics')->insert(['school_id' => 10, 'academic_year_id' => 10, 'user_id' => $id, 'standardLink_id' => 1]);
            DB::table('student_parent_links')->insert(['school_id' => 10, 'student_id' => $id, 'parent_id' => 4]);
        }
        DB::enableQueryLog(); DB::flushQueryLog();
        $processor = new class { use \App\Traits\MemberProcess; };
        $request = Request::create('/admin/students/find', 'GET', ['status' => 'active']);
        $rows = $processor->MemberFilter($request, 10, 6, 'active')->resolve($request);
        $this->assertCount(22, $rows);
        $this->assertLessThanOrEqual(9, count(DB::getQueryLog()));
        $row = collect($rows)->firstWhere('id', 100);
        $this->assertSame([4], $row['parent_id']);
        $this->assertNotEmpty($row['class']);
        $this->assertArrayHasKey('librarycard_number', $row);
        $this->assertNull($row['librarycard_number']);
    }

    public function test_school_only_actions_are_blocked_but_homework_is_allowed(): void
    {
        $middleware = new AcademyBackend;
        $request = Request::create('/admin/promotion/create', 'POST');
        $request->headers->set('Accept', 'application/json'); $request->setUserResolver(fn () => $this->actor(1, 3));
        $this->assertSame(410, $middleware->handle($request, fn () => response('must not run'))->status());
        $request = Request::create('/admin/homeworks'); $request->setUserResolver(fn () => $this->actor(1, 3));
        $this->assertSame('homework allowed', $middleware->handle($request, fn () => response('homework allowed'))->getContent());
    }

    public function test_index_migration_is_idempotent_and_reversible_without_data_loss(): void
    {
        $migration = require database_path('migrations/2026_10_30_000001_add_academy_learning_indexes.php');
        $migration->up(); $migration->up();
        $this->assertTrue(Schema::hasIndex('users', 'academy_users_lookup'));
        $this->assertTrue(Schema::hasIndex('homeworks', 'academy_homework_lookup'));
        $migration->down();
        $this->assertFalse(Schema::hasIndex('users', 'academy_users_lookup'));
        $this->assertDatabaseCount('users', 7);
    }
}
