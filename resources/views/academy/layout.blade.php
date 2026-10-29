<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('academy.name') }} — إدارة الأكاديمية</title>
    <style>
        :root{color-scheme:light;--ink:#162c3e;--accent:#176e71;--line:#dbe5e8}
        *{box-sizing:border-box}body{margin:0;background:#f3f7f8;color:var(--ink);font:16px/1.8 system-ui,sans-serif}
        a{color:var(--accent)}header{background:#fff;border-bottom:1px solid var(--line);padding:22px 5%;display:flex;justify-content:space-between;gap:20px;align-items:center;flex-wrap:wrap}
        .brand{font-size:24px;font-weight:800;text-decoration:none}small,.muted{color:#526879}main{max-width:1180px;margin:auto;padding:28px 20px}h1,h2,h3{line-height:1.5}h1{font-size:30px;margin:8px 0}h2{font-size:22px}h3{font-size:18px}
        nav{display:flex;gap:18px;align-items:center;flex-wrap:wrap}.card{background:white;border:1px solid var(--line);border-radius:16px;padding:24px;margin:20px 0}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:18px}.grid .card{margin:0}
        .count{font-size:34px;font-weight:800;color:var(--accent)}.levels{display:flex;gap:12px;flex-wrap:wrap}.level{background:#e8f3f1;padding:12px 18px;border-radius:12px}.badge{display:inline-block;padding:2px 12px;background:#e8f3f1;border-radius:20px;font-size:14px}
        button,.button{border:0;border-radius:9px;padding:10px 18px;background:var(--accent);color:white;cursor:pointer;font:inherit;text-decoration:none;display:inline-block}.secondary{background:#e8eef1;color:var(--ink)}.danger{background:#983c36}input,select,textarea{font:inherit;width:100%;padding:10px;border:1px solid #aebfc6;border-radius:8px;background:white;color:var(--ink)}input[type=checkbox]{width:auto}label{display:block;margin:10px 0 5px}textarea{min-height:110px}.fields{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;align-items:end}
        .notice{padding:16px;border-radius:10px;background:#dff3e9;margin:16px 0}.error{background:#fde9e6}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;text-align:right}td,th{padding:14px;border-bottom:1px solid var(--line);vertical-align:top}.inline{display:inline}footer{padding:24px;text-align:center;color:#526879}.student-row{border-top:1px solid var(--line);padding-top:18px;margin-top:24px}details summary{cursor:pointer;font-weight:700}button:focus-visible,a:focus-visible{outline:3px solid #cb8a2a;outline-offset:4px}@media(max-width:600px){main{padding:18px 12px}.card{padding:18px}header{padding:18px}h1{font-size:24px}}
    </style>
</head>
<body>
<header>
    <div><a class="brand" href="{{ route('academy.index') }}">{{ config('academy.name') }}</a><br><small>Bridge Academy · ستة مستويات · تعلّم بدون إعادة</small></div>
    <nav aria-label="التنقل الرئيسي">
        <a href="{{ route('academy.index') }}">الرئيسية</a>
        @if((int) $actor->usergroup_id === 3)
            <a href="{{ url('/admin/students') }}">الطلاب</a>
            <a href="{{ url('/admin/teachers') }}">المدرّسون</a>
            <a href="{{ url('/admin/parents') }}">أولياء الأمور</a>
            <a href="{{ url('/admin/dashboard') }}">باقي وظائف الإدارة</a>
        @elseif((int) $actor->usergroup_id === 5)
            <a href="{{ url('/teacher/dashboard') }}">باقي أدوات المدرّس</a>
        @endif
        <form method="post" action="{{ route('logout') }}">@csrf<button class="secondary">تسجيل خروج</button></form>
    </nav>
</header>
<main>
    @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="notice error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
<footer>التقييم لمتابعة التطوّر، وليس للنجاح والرسوب.</footer>
</body>
</html>
