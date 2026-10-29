@extends('academy.layout')
@section('content')
<h1>أهلًا، {{ $actor->name }}</h1>
<p class="muted">الجروبات، تقدّم الطلاب، وفيدباك المدرّسين في مكان واحد.</p>
@if($counts)
<div class="grid">
    <div class="card">الجروبات النشطة<div class="count">{{ $counts['groups'] }}</div></div>
    <div class="card">الطلاب<div class="count">{{ $counts['students'] }}</div></div>
    <div class="card">المدرّسون<div class="count">{{ $counts['teachers'] }}</div></div>
</div>
@endif
<section class="card" aria-label="خريطة المنهج">
    <h2>رحلة Bridge Academy</h2>
    <div class="levels">@for($level = 1; $level <= 6; $level++)<div class="level"><strong>المستوى {{ $level }}</strong><br><small>Unit 1 + Unit 2</small></div>@endfor</div>
    <p class="muted">بعد إتمام الوحدتين يكتب المدرّس الفيدباك وينقل الطالب للمستوى التالي. لا توجد درجة نجاح مطلوبة.</p>
</section>
@if($groups !== null)
<section class="card">
    <h2>{{ (int) $actor->usergroup_id === 5 ? 'جروباتي' : 'الجروبات' }}</h2>
    <div class="table-wrap"><table><thead><tr><th>الجروب</th><th>المستوى</th><th>المواعيد</th><th></th></tr></thead><tbody>
    @forelse($groups as $group)
        <tr><td>{{ $group->name }}</td><td>{{ $group->level }}</td><td>{{ $group->schedule ?: 'لم تحدد بعد' }}</td><td><a href="{{ route('academy.groups.show', $group->id) }}">فتح الجروب</a></td></tr>
    @empty<tr><td colspan="4">لا توجد جروبات بعد.</td></tr>@endforelse
    </tbody></table></div>
    {{ $groups->links() }}
</section>
@else
<section class="card"><h2>{{ (int) $actor->usergroup_id === 7 ? 'متابعة أولادي' : 'ملفي وتقدّمي' }}</h2>
@forelse($people as $person)<p><a href="{{ route('academy.student', $person->id) }}">{{ $person->name }} — المستويات والفيدباك</a></p>
@empty<p>لا يوجد طالب مرتبط بالحساب. راجع إدارة الأكاديمية.</p>@endforelse
</section>
@endif
@if((int) $actor->usergroup_id === 3)
<section class="card"><h2>إضافة جروب</h2>
<form method="post" action="{{ route('academy.groups.store') }}">@csrf
    <div class="fields">
        <div><label for="name">اسم الجروب</label><input id="name" name="name" maxlength="120" value="{{ old('name') }}" required></div>
        <div><label for="level">المستوى</label><select id="level" name="level">@for($i=1;$i<=6;$i++)<option value="{{ $i }}" @selected((int) old('level', 1) === $i)>المستوى {{ $i }}</option>@endfor</select></div>
        <div><label for="teacher">المدرّس</label><select id="teacher" name="teacher_id"><option value="">تعيين لاحقًا</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string) old('teacher_id') === (string) $teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
        <div><label for="schedule">المواعيد والمنطقة الزمنية</label><input id="schedule" name="schedule" maxlength="250" value="{{ old('schedule') }}" placeholder="السبت والثلاثاء، 6 مساءً بتوقيت القاهرة"></div>
        <button>إنشاء الجروب</button>
    </div>
</form></section>
@if($waiting->isNotEmpty())
<section class="card"><h2>طلاب ينتظرون جروب المستوى التالي</h2><p class="muted">أحدث 30 طالبًا. افتح جروبًا بالمستوى المطابق لإضافة الطالب إليه.</p>
@foreach($waiting as $person)<p><a href="{{ route('academy.student', $person->id) }}">{{ $person->name }}</a> · المستوى {{ $person->level }}</p>@endforeach
</section>
@endif
@endif
@endsection
