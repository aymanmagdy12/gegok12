<?php

namespace App\Support;

class AcademyNavigation
{
    /** A short list of daily academy tasks; no school administration sections. */
    public static function sections(int $role): array
    {
        $groups = ['لوحة الأكاديمية' => '/academy', 'الجروبات والمستويات' => '/academy#groups'];
        $sections = match ($role) {
            3 => [
                'الأكاديمية' => $groups,
                'الأشخاص' => ['الطلاب' => '/admin/students', 'المدرّسون' => '/admin/teachers', 'أولياء الأمور' => '/admin/parents'],
                'التعلّم' => ['الواجبات' => '/admin/homeworks', 'الحضور' => '/admin/attendance/add',
                    'مواد التعلّم' => '/admin/classwall/pages', 'الحصص المباشرة' => '/admin/video-conference'],
                'المتابعة' => ['أعياد الميلاد' => '/academy/birthdays', 'الإعلانات' => '/admin/notices',
                    'الرسائل' => '/admin/conversations', 'الأنشطة' => '/admin/events', 'المدفوعات' => '/admin/fees'],
            ],
            5 => [
                'الأكاديمية' => $groups,
                'التعلّم' => ['الواجبات' => '/teacher/homeworks', 'الحضور' => '/teacher/attendance/add',
                    'تحضير الحصص' => '/teacher/lessonplans', 'تدريبات اللغة' => '/teacher/quiz',
                    'مواد التعلّم' => '/teacher/classwall/pages', 'الحصص المباشرة' => '/teacher/video-conference'],
                'المتابعة' => ['الإعلانات' => '/teacher/notices', 'الرسائل' => '/teacher/conversations', 'الأنشطة' => '/teacher/events'],
            ],
            6 => [
                'تعلّمي' => ['مستواي والفيدباك' => '/academy', 'واجباتي' => '/student/homeworks',
                    'تدريبات اللغة' => '/student/quiz', 'مواد التعلّم' => '/student/classwall/pages',
                    'الحصص المباشرة' => '/student/video-conference'],
                'المتابعة' => ['الإعلانات' => '/student/notices', 'الرسائل' => '/student/conversations', 'الأنشطة' => '/student/events'],
            ],
            // Parent accounts have their own linked-child view, not teacher/student write screens.
            7 => ['أولادي' => ['تقدّم أولادي والفيدباك' => '/academy']],
            default => [],
        };
        // Some upstream menu entries refer to optional, uninstalled add-ons.
        // Keep them when installed, but do not expose dead links in the simple UI.
        $available = [];
        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
            if (in_array('GET', $route->methods(), true)) {
                $available['/'.ltrim($route->uri(), '/')] = true;
            }
        }
        foreach ($sections as $name => $items) {
            $sections[$name] = array_filter($items, fn ($path) => isset($available[parse_url($path, PHP_URL_PATH)]));
        }
        return array_filter($sections);
    }
}
