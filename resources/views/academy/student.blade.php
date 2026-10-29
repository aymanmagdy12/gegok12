@extends('academy.layout')
@section('content')
<a href="{{ route('academy.index') }}">العودة للرئيسية</a>
<h1>{{ $student->name }}</h1>
<section class="card"><h2>التقدّم في Bridge Academy</h2>
@if($enrollment)
    <p class="badge">{{ $enrollment->completed_at ? 'أتمّ المستويات الستة' : 'المستوى الحالي: '.$enrollment->level }}</p>
    <p>Unit 1: {{ $enrollment->unit_one_done ? 'مكتملة' : 'قيد الدراسة' }} · Unit 2: {{ $enrollment->unit_two_done ? 'مكتملة' : 'قيد الدراسة' }}</p>
    @if(!$enrollment->group_id && !$enrollment->completed_at)<p class="muted">جاهز للمستوى التالي؛ الإدارة ستعيّن الجروب المناسب.</p>@endif
@else<p>لم يتم تسجيل الطالب في جروب المنهج بعد.</p>@endif
</section>
<section class="card"><h2>سجل فيدباك المدرّس</h2>
@forelse($feedback as $entry)
    <article class="student-row"><h3>المستوى {{ $entry->level }} · اكتملت الوحدتان</h3><small>{{ $entry->created_at }}</small><p style="white-space:pre-wrap">{{ $entry->feedback }}</p></article>
@empty<p>سيظهر الفيدباك هنا بعد إتمام أول مستوى. لا توجد نتيجة نجاح أو رسوب.</p>@endforelse
</section>
@endsection
