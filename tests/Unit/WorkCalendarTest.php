<?php

namespace Tests\Unit;

use App\Domain\WorkCalendar;
use PHPUnit\Framework\TestCase;

// 2 Nov 2026 is a Monday, 25 Dec 2026 a Friday
class WorkCalendarTest extends TestCase
{
    public function test_a_normal_weekday_is_a_working_day(): void
    {
        $this->assertTrue((new WorkCalendar())->isWorkingDay('2026-11-02'));
    }

    public function test_weekends_are_not_working_days(): void
    {
        $calendar = new WorkCalendar();

        $this->assertFalse($calendar->isWorkingDay('2026-10-31')); // Saturday
        $this->assertFalse($calendar->isWorkingDay('2026-11-01')); // Sunday
    }

    public function test_a_public_holiday_is_not_a_working_day(): void
    {
        $calendar = new WorkCalendar(['2026-12-25']);

        $this->assertFalse($calendar->isWorkingDay('2026-12-25'));
        $this->assertTrue($calendar->isWorkingDay('2026-12-24'));
    }

    public function test_everybody_is_available_on_a_normal_day(): void
    {
        $this->assertSame(1.0, (new WorkCalendar())->availableShare('2026-11-02', [1, 2]));
    }

    public function test_one_absent_consultant_of_two_makes_it_half(): void
    {
        $calendar = new WorkCalendar([], [['user_id' => 2, 'from' => '2026-11-16', 'to' => '2026-11-20']]);

        $this->assertSame(0.5, $calendar->availableShare('2026-11-18', [1, 2]));
    }

    public function test_the_first_and_last_day_of_an_absence_count(): void
    {
        $calendar = new WorkCalendar([], [['user_id' => 2, 'from' => '2026-11-16', 'to' => '2026-11-20']]);

        $this->assertSame(0.5, $calendar->availableShare('2026-11-16', [1, 2]));
        $this->assertSame(0.5, $calendar->availableShare('2026-11-20', [1, 2]));
        $this->assertSame(1.0, $calendar->availableShare('2026-11-13', [1, 2]));
        $this->assertSame(1.0, $calendar->availableShare('2026-11-23', [1, 2]));
    }

    public function test_nobody_is_available_when_everybody_is_absent(): void
    {
        $calendar = new WorkCalendar([], [
            ['user_id' => 1, 'from' => '2026-11-16', 'to' => '2026-11-20'],
            ['user_id' => 2, 'from' => '2026-11-16', 'to' => '2026-11-20'],
        ]);

        $this->assertSame(0.0, $calendar->availableShare('2026-11-17', [1, 2]));
    }

    public function test_the_absence_of_someone_not_on_the_order_does_not_matter(): void
    {
        $calendar = new WorkCalendar([], [['user_id' => 99, 'from' => '2026-11-16', 'to' => '2026-11-20']]);

        $this->assertSame(1.0, $calendar->availableShare('2026-11-18', [1, 2]));
    }

    public function test_nobody_is_available_on_a_weekend_a_holiday_or_without_a_team(): void
    {
        $calendar = new WorkCalendar(['2026-12-25']);

        $this->assertSame(0.0, $calendar->availableShare('2026-11-01', [1, 2]));
        $this->assertSame(0.0, $calendar->availableShare('2026-12-25', [1, 2]));
        $this->assertSame(0.0, $calendar->availableShare('2026-11-02', []));
    }

    public function test_it_counts_working_days_between_two_dates(): void
    {
        $calendar = new WorkCalendar();

        // Monday to Sunday is one working week
        $this->assertSame(5, $calendar->workingDaysBetween('2026-11-02', '2026-11-08'));
        // the same day counts once
        $this->assertSame(1, $calendar->workingDaysBetween('2026-11-02', '2026-11-02'));
    }

    public function test_a_holiday_takes_a_day_out_of_the_count(): void
    {
        $calendar = new WorkCalendar(['2026-12-25']);

        // Monday 21 Dec to Sunday 27 Dec, minus Friday the 25th
        $this->assertSame(4, $calendar->workingDaysBetween('2026-12-21', '2026-12-27'));
    }
}
