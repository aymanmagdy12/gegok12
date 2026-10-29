<nav class="navbar bg-white w-full flex flex-wrap px-4 py-3 justify-between items-center" aria-label="الأكاديمية" dir="rtl">
    <div class="flex items-center">
        <button type="button" class="block lg:hidden md:hidden px-3" aria-label="فتح القائمة" onclick="showsidebar('res_sidebar')">☰</button>
        <a class="text-xl font-semibold" href="{{ route('academy.index') }}">{{ config('academy.name') }}</a>
        <span class="hidden lg:block px-3 text-sm text-gray-600">إنجليزي للأطفال · 6–15 سنة</span>
    </div>
    <div class="flex items-center">
        <a href="{{ route('academy.index') }}" class="px-3">الرئيسية</a>
        <span class="px-3">{{ auth()->user()->name }}</span>
        @if(method_exists(auth()->user(), 'isImpersonating') && auth()->user()->isImpersonating())
            <a href="{{ url('/teacher/impersonate/stop') }}" class="px-3">العودة لحسابي</a>
        @else
            <form action="{{ route('logout') }}" method="post">@csrf<button type="submit" class="px-3">تسجيل خروج</button></form>
        @endif
    </div>
</nav>
