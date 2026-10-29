@php($academySections = \App\Support\AcademyNavigation::sections((int) auth()->user()->usergroup_id))
<nav aria-label="قائمة الأكاديمية" dir="rtl" style="padding:16px;line-height:1.8">
@foreach($academySections as $section => $items)
    <div style="margin-bottom:22px">
        <p style="font-size:12px;opacity:.75;margin-bottom:6px">{{ $section }}</p>
        @foreach($items as $label => $path)
            <a href="{{ url($path) }}" style="display:block;padding:7px 0;color:inherit;text-decoration:none">{{ $label }}</a>
        @endforeach
    </div>
@endforeach
</nav>
