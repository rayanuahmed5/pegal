<?php

namespace App\Domain;

use App\Models\Absence;
use App\Models\Order;
use App\Models\PublicHoliday;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

// loads every order with its budget and (if asked) its forecast
// two pages need exactly this, the budget page and the follow-up list, so it lives in one place
class OrderOverview
{
    /**
     * @return Collection<int, array{order: Order, budget: OrderBudget, forecast: ?ForecastResult}>
     */
    public function rows(CarbonImmutable $today, bool $withForecast): Collection
    {
        // finance doesn't get a forecast (role matrix), so it is not even calculated for them
        $forecaster = $withForecast ? new BudgetForecast($this->workCalendar()) : null;

        // one query for all orders, the hours are summed in SQL
        $orders = Order::query()
            ->with(['supplier', 'consultants'])
            ->withSum(
                ['bookings as used_hours' => fn ($q) => $q->where('status', '!=', BookingStatus::Rejected->value)],
                'hours',
            )
            ->withSum(
                ['bookings as window_hours' => fn ($q) => $q
                    ->where('status', '!=', BookingStatus::Rejected->value)
                    ->whereBetween('worked_on', [
                        BurnRate::windowStart($today)->toDateString(),
                        BurnRate::windowEnd($today)->toDateString(),
                    ])],
                'hours',
            )
            ->orderBy('number')
            ->get();

        return $orders->map(function (Order $order) use ($forecaster, $today) {
            $budget = OrderBudget::fromHours((float) $order->budget_pd, (float) $order->used_hours);

            $forecast = $forecaster?->calculate(
                orderedPd: $budget->orderedPd,
                remainingPd: $budget->remainingPd(),
                burnPerWeek: BurnRate::perWeek((float) $order->window_hours / TimeBooking::HOURS_PER_PERSON_DAY),
                today: $today,
                orderEnd: CarbonImmutable::parse($order->ends_on->toDateString()),
                reorderLeadWeeks: $order->reorder_lead_weeks,
                consultantIds: $order->consultants->pluck('id')->all(),
            );

            return ['order' => $order, 'budget' => $budget, 'forecast' => $forecast];
        });
    }

    /**
     * The orders that need a follow-up order (overdue or due within 6 weeks), most urgent first.
     * Each row also has 'status' (overdue or soon) and 'days' until the reorder date.
     *
     * @return Collection<int, array{order: Order, budget: OrderBudget, forecast: ForecastResult, status: string, days: int}>
     */
    public function followUps(CarbonImmutable $today): Collection
    {
        return $this->rows($today, withForecast: true)
            ->map(fn (array $row) => $row + [
                'status' => FollowUp::status($row['forecast'], $today),
                'days' => FollowUp::daysUntilReorder($row['forecast'], $today),
            ])
            ->filter(fn (array $row) => $row['status'] !== null)
            // earliest reorder date first, so the overdue ones sit at the top
            ->sortBy(fn (array $row) => $row['forecast']->reorderDate->toDateString())
            ->values();
    }

    // holidays and vacations as plain arrays, the forecast classes know nothing about the database
    private function workCalendar(): WorkCalendar
    {
        $holidays = PublicHoliday::query()->pluck('holiday_on')
            ->map(fn ($day) => $day->toDateString())
            ->all();

        $absences = Absence::query()->get()
            ->map(fn (Absence $absence) => [
                'user_id' => $absence->user_id,
                'from' => $absence->starts_on->toDateString(),
                'to' => $absence->ends_on->toDateString(),
            ])
            ->all();

        return new WorkCalendar($holidays, $absences);
    }
}
