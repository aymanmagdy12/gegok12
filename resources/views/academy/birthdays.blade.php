@extends('academy.layout')
@section('content')
<h1>أعياد الميلاد 🎂</h1>
<p class="muted">طلاب ومدرّسو الأكاديمية خلال الثلاثين يومًا الجاية، بتوقيت القاهرة.</p>
<div class="card">
@forelse($birthdays as $person)
    <article class="student-row">
        <h2>{{ $person->name }}</h2>
        <p><span class="badge">{{ (int) $person->usergroup_id === 6 ? 'طالب' : 'مدرّس' }}</span>
            {{ $person->days_away === 0 ? 'عيد ميلاده النهارده!' : 'بعد '.$person->days_away.' يوم' }} · {{ $person->birthday }}</p>
        <p class="muted">كل سنة وأنت طيب يا {{ $person->name }}! نتمنى لك سنة مليانة تعلّم ومرح 🎉</p>
    </article>
@empty<p>مفيش أعياد ميلاد مسجّلة خلال الثلاثين يومًا الجاية.</p>@endforelse
</div>
<p class="muted">التواريخ مأخوذة من ملفات الأشخاص. الرسالة مقترح للتهنئة وليست رسالة تم إرسالها.</p>
@endsection
