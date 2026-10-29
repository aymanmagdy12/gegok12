@extends('academy.layout')
@section('content')
<a href="{{ route('academy.index') }}">العودة للرئيسية</a>
<h1>{{ $group->name }}</h1>
<p><span class="badge">المستوى {{ $group->level }}</span> {{ $group->schedule }}</p>
@if((int) $actor->usergroup_id === 3)
<section class="card"><details><summary>تعديل الجروب</summary>
<form method="post" action="{{ route('academy.groups.update', $group->id) }}">@csrf @method('PATCH')
<div class="fields">
    <div><label for="name">الاسم</label><input id="name" name="name" maxlength="120" value="{{ old('name', $group->name) }}" required></div>
    <div><label for="level">المستوى</label><select id="level" name="level">@for($i=1;$i<=6;$i++)<option value="{{ $i }}" @selected((int) old('level', $group->level) === $i)>{{ $i }}</option>@endfor</select></div>
    <div><label for="teacher">المدرّس</label><select id="teacher" name="teacher_id"><option value="">بدون مدرس</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string) old('teacher_id', $group->teacher_id) === (string) $teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
    <div><label for="schedule">المواعيد</label><input id="schedule" name="schedule" maxlength="250" value="{{ old('schedule', $group->schedule) }}"></div>
    <button>حفظ</button>
</div></form>
<p class="muted">لتغيير المستوى أو أرشفة الجروب، انقل الطلاب أولًا. الأرشفة لا تحذف الفيدباك.</p>
<form method="post" action="{{ route('academy.groups.archive', $group->id) }}">@csrf @method('DELETE')<button class="danger">أرشفة الجروب الفارغ</button></form>
</details></section>
<section class="card"><h2>إضافة طالب أو نقله لهذا الجروب</h2>
<form method="get" action="{{ route('academy.groups.show', $group->id) }}"><label for="search">البحث بالاسم</label><div class="fields"><input id="search" name="search" value="{{ request('search') }}" maxlength="120"><button class="secondary">بحث</button></div></form>
<form method="post" action="{{ route('academy.enroll', $group->id) }}">@csrf
<label for="student">الطالب — أول 100 نتيجة</label><select name="student_id" id="student" required><option value="">اختار طالبًا</option>@foreach($candidates as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->name }}</option>@endforeach</select>
<p class="muted">الطالب الجديد يبدأ من مستوى هذا الجروب. الطالب المسجّل يُنقل فقط لجروب يطابق مستواه الحالي، مع الاحتفاظ بتقدّمه.</p>
<button>إضافة / نقل الطالب</button></form></section>
@endif
<section class="card"><h2>طلاب الجروب</h2>
@forelse($enrollments as $enrollment)
<article class="student-row">
    <h3><a href="{{ route('academy.student', $enrollment->student_id) }}">{{ $enrollment->name }}</a></h3>
    <p>Unit 1: {{ $enrollment->unit_one_done ? 'مكتملة' : 'قيد الدراسة' }} · Unit 2: {{ $enrollment->unit_two_done ? 'مكتملة' : 'قيد الدراسة' }}</p>
    @if((int) $actor->usergroup_id === 5)
    <form method="post" action="{{ route('academy.units', $enrollment->id) }}">@csrf @method('PATCH')
        <input type="hidden" name="level" value="{{ $enrollment->level }}">
        <input type="hidden" name="unit_one_done" value="0"><label><input type="checkbox" name="unit_one_done" value="1" @checked($enrollment->unit_one_done)> اكتملت Unit 1</label>
        <input type="hidden" name="unit_two_done" value="0"><label><input type="checkbox" name="unit_two_done" value="1" @checked($enrollment->unit_two_done)> اكتملت Unit 2</label>
        <button class="secondary">حفظ إتمام الوحدات</button>
    </form>
    @if($enrollment->unit_one_done && $enrollment->unit_two_done)
    <form method="post" action="{{ route('academy.complete', $enrollment->id) }}">@csrf
        <input type="hidden" name="level" value="{{ $enrollment->level }}">
        <label for="feedback-{{ $enrollment->id }}">فيدباك المدرّس — يظهر للطالب وولي الأمر</label>
        <textarea id="feedback-{{ $enrollment->id }}" name="feedback" maxlength="5000" required placeholder="التقدّم، نقاط القوة، واقتراحات التدريب">{{ old('feedback') }}</textarea>
        <p class="muted">{{ (int) $enrollment->level === 6 ? 'سيُسجّل إتمام المستويات الستة.' : 'سيُحفظ الفيدباك ويصبح الطالب جاهزًا لجروب المستوى التالي.' }} لا يوجد رسوب أو إعادة.</p>
        <button>{{ (int) $enrollment->level === 6 ? 'حفظ الفيدباك وإتمام البرنامج' : 'حفظ الفيدباك والانتقال للمستوى التالي' }}</button>
    </form>
    @endif
    @endif
</article>
@empty<p>الجروب لسه فاضي.</p>@endforelse
{{ $enrollments->links() }}
</section>
@endsection
