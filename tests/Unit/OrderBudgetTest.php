<?php

namespace Tests\Unit;

use App\Domain\OrderBudget;
use PHPUnit\Framework\TestCase;

// plain numbers in, plain numbers out, no database needed
class OrderBudgetTest extends TestCase
{
    public function test_hours_become_person_days(): void
    {
        $budget = OrderBudget::fromHours(10, 36);

        $this->assertSame(4.5, $budget->usedPd);
        $this->assertSame(5.5, $budget->remainingPd());
        $this->assertSame(45.0, $budget->percentUsed());
    }

    public function test_a_fresh_order_is_ok(): void
    {
        $budget = OrderBudget::fromHours(10, 0);

        $this->assertSame(10.0, $budget->remainingPd());
        $this->assertSame('ok', $budget->level());
    }

    public function test_the_bar_turns_amber_at_eighty_percent(): void
    {
        $this->assertSame('ok', OrderBudget::fromHours(10, 63.2)->level());      // 79%
        $this->assertSame('warning', OrderBudget::fromHours(10, 64)->level());   // 80%
    }

    public function test_a_used_up_order_is_full(): void
    {
        $budget = OrderBudget::fromHours(10, 80);

        $this->assertSame(0.0, $budget->remainingPd());
        $this->assertSame('full', $budget->level());
    }

    public function test_remaining_never_goes_below_zero_and_percent_stops_at_100(): void
    {
        // can't happen through the booking rules, but old or imported data might
        $budget = OrderBudget::fromHours(10, 96);

        $this->assertSame(0.0, $budget->remainingPd());
        $this->assertSame(100.0, $budget->percentUsed());
    }

    public function test_an_order_without_budget_counts_as_full(): void
    {
        $this->assertSame('full', OrderBudget::fromHours(0, 0)->level());
    }
}
