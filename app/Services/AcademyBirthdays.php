<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AcademyBirthdays
{
    public function upcoming(int $schoolId, int $days = 30): Collection
    {
        $today = CarbonImmutable::now('Africa/Cairo')->startOfDay();
        return DB::table('userprofiles as p')->join('users as u', 'u.id', '=', 'p.user_id')
            ->where('p.school_id', $schoolId)->where('u.school_id', $schoolId)
            ->whereIn('u.usergroup_id', [5, 6])->whereNull('u.deleted_at')->whereNull('p.deleted_at')
            ->whereNotNull('p.date_of_birth')->where('p.status', 'active')
            ->get(['u.id', 'u.name', 'u.usergroup_id', 'p.date_of_birth'])
            ->map(function ($person) use ($today) {
                $dob = CarbonImmutable::parse($person->date_of_birth, 'Africa/Cairo');
                $next = $this->inYear($dob, $today->year);
                if ($next->lessThan($today)) {
                    $next = $this->inYear($dob, $today->year + 1);
                }
                $person->birthday = $next->format('Y-m-d');
                $person->days_away = (int) $today->diffInDays($next);
                return $person;
            })
            ->filter(fn ($person) => $person->days_away <= $days)
            ->sortBy('birthday')->values();
    }

    private function inYear(CarbonImmutable $dob, int $year): CarbonImmutable
    {
        $month = CarbonImmutable::create($year, $dob->month, 1, 0, 0, 0, 'Africa/Cairo');
        // Celebrate February 29 on February 28 in non-leap years.
        return $month->day(min($dob->day, $month->daysInMonth));
    }
}
