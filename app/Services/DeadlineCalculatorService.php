<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DeadlineCalculatorService
{
    private Collection $holidays;
    
    public function __construct()
    {
        $this->loadHolidays();
    }

    /**
     * Calculate deadline considering business days and holidays
     */
    public function calculateDeadline(
        Carbon $startDate,
        int $businessDays,
        ?string $state = null,
        ?string $city = null
    ): Carbon {
        $currentDate = $startDate->copy();
        $daysAdded = 0;
        
        while ($daysAdded < $businessDays) {
            $currentDate->addDay();
            
            if ($this->isBusinessDay($currentDate, $state, $city)) {
                $daysAdded++;
            }
        }
        
        return $currentDate;
    }

    /**
     * Check if date is a business day
     */
    public function isBusinessDay(Carbon $date, ?string $state = null, ?string $city = null): bool
    {
        // Check if it's weekend
        if ($date->isWeekend()) {
            return false;
        }
        
        // Check if it's a holiday
        return !$this->isHoliday($date, $state, $city);
    }

    /**
     * Check if date is a holiday
     */
    public function isHoliday(Carbon $date, ?string $state = null, ?string $city = null): bool
    {
        $dateString = $date->format('Y-m-d');
        
        // Check national holidays
        if ($this->holidays->where('date', $dateString)->where('is_national', true)->isNotEmpty()) {
            return true;
        }
        
        // Check state holidays
        if ($state && $this->holidays->where('date', $dateString)->where('state', $state)->isNotEmpty()) {
            return true;
        }
        
        // Check city holidays
        if ($city && $this->holidays->where('date', $dateString)->where('city', $city)->isNotEmpty()) {
            return true;
        }
        
        return false;
    }

    /**
     * Count business days between two dates
     */
    public function countBusinessDays(
        Carbon $startDate,
        Carbon $endDate,
        ?string $state = null,
        ?string $city = null
    ): int {
        $currentDate = $startDate->copy();
        $businessDays = 0;
        
        while ($currentDate->lte($endDate)) {
            if ($this->isBusinessDay($currentDate, $state, $city)) {
                $businessDays++;
            }
            $currentDate->addDay();
        }
        
        return $businessDays;
    }

    /**
     * Calculate appeal deadline (15 days for most cases)
     */
    public function calculateAppealDeadline(Carbon $publicationDate, ?string $state = null): Carbon
    {
        return $this->calculateDeadline($publicationDate, 15, $state);
    }

    /**
     * Calculate response deadline (15 days for contestation)
     */
    public function calculateResponseDeadline(Carbon $citationDate, ?string $state = null): Carbon
    {
        return $this->calculateDeadline($citationDate, 15, $state);
    }

    /**
     * Calculate execution deadline (varies by case type)
     */
    public function calculateExecutionDeadline(
        Carbon $startDate,
        string $executionType = 'payment',
        ?string $state = null
    ): Carbon {
        $days = match ($executionType) {
            'payment' => 15,
            'obligation_to_do' => 30,
            'obligation_not_to_do' => 10,
            default => 15
        };
        
        return $this->calculateDeadline($startDate, $days, $state);
    }

    /**
     * Calculate prescription deadline (varies by case type)
     */
    public function calculatePrescriptionDeadline(
        Carbon $startDate,
        string $prescriptionType
    ): Carbon {
        $years = match ($prescriptionType) {
            'civil_general' => 10,
            'civil_contract' => 3,
            'labor' => 2,
            'consumer' => 5,
            'tax' => 5,
            default => 10
        };
        
        return $startDate->copy()->addYears($years);
    }

    /**
     * Get next business day
     */
    public function getNextBusinessDay(Carbon $date, ?string $state = null, ?string $city = null): Carbon
    {
        $nextDay = $date->copy()->addDay();
        
        while (!$this->isBusinessDay($nextDay, $state, $city)) {
            $nextDay->addDay();
        }
        
        return $nextDay;
    }

    /**
     * Get previous business day
     */
    public function getPreviousBusinessDay(Carbon $date, ?string $state = null, ?string $city = null): Carbon
    {
        $previousDay = $date->copy()->subDay();
        
        while (!$this->isBusinessDay($previousDay, $state, $city)) {
            $previousDay->subDay();
        }
        
        return $previousDay;
    }

    /**
     * Load holidays from database
     */
    private function loadHolidays(): void
    {
        $currentYear = now()->year;
        
        $this->holidays = Holiday::whereYear('date', $currentYear)
            ->orWhereYear('date', $currentYear + 1)
            ->get()
            ->map(function ($holiday) {
                return [
                    'date' => $holiday->date->format('Y-m-d'),
                    'is_national' => $holiday->is_national,
                    'state' => $holiday->state,
                    'city' => $holiday->city,
                ];
            });
    }

    /**
     * Add fixed Brazilian holidays for a year
     */
    public function addBrazilianHolidays(int $year): void
    {
        $holidays = [
            // Fixed holidays
            ['name' => 'Confraternização Universal', 'date' => "$year-01-01", 'is_national' => true],
            ['name' => 'Tiradentes', 'date' => "$year-04-21", 'is_national' => true],
            ['name' => 'Dia do Trabalhador', 'date' => "$year-05-01", 'is_national' => true],
            ['name' => 'Independência do Brasil', 'date' => "$year-09-07", 'is_national' => true],
            ['name' => 'Nossa Senhora Aparecida', 'date' => "$year-10-12", 'is_national' => true],
            ['name' => 'Finados', 'date' => "$year-11-02", 'is_national' => true],
            ['name' => 'Proclamação da República', 'date' => "$year-11-15", 'is_national' => true],
            ['name' => 'Natal', 'date' => "$year-12-25", 'is_national' => true],
        ];

        // Calculate Easter-related holidays
        $easter = $this->calculateEaster($year);
        $holidays[] = ['name' => 'Sexta-feira Santa', 'date' => $easter->copy()->subDays(2)->format('Y-m-d'), 'is_national' => true];
        $holidays[] = ['name' => 'Corpus Christi', 'date' => $easter->copy()->addDays(60)->format('Y-m-d'), 'is_national' => true];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                [
                    'date' => $holiday['date'],
                    'is_national' => $holiday['is_national'],
                    'state' => null,
                    'city' => null,
                ],
                ['name' => $holiday['name']]
            );
        }
    }

    /**
     * Calculate Easter date for a given year
     */
    private function calculateEaster(int $year): Carbon
    {
        $a = $year % 19;
        $b = intval($year / 100);
        $c = $year % 100;
        $d = intval($b / 4);
        $e = $b % 4;
        $f = intval(($b + 8) / 25);
        $g = intval(($b - $f + 1) / 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intval($c / 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intval(($a + 11 * $h + 22 * $l) / 451);
        $month = intval(($h + $l - 7 * $m + 114) / 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day);
    }
}