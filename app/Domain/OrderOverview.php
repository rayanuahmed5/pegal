<?php

namespace App\Domain;

use App\Models\Absence;
use App\Models\Order;
use App\Models\PublicHoliday;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

        $weekly = $withForecast ? $this->weeklyBurn($today) : [];

        return $orders->map(function (Order $order) use ($forecaster, $today, $weekly) {
            $budget = OrderBudget::fromHours((float) $order->budget_pd, (float) $order->used_hours);

            [$slow, $fast] = BurnRate::slowestAndFastest($weekly[$order->id] ?? []);

            $forecast = $forecaster?->calculate(
                orderedPd: $budget->orderedPd,
                remainingPd: $budget->remainingPd(),
                burnPerWeek: BurnRate::perWeek((float) $order->window_hours / TimeBooking::HOURS_PER_PERSON_DAY),
                today: $today,
                orderEnd: CarbonImmutable::parse($order->ends_on->toDateString()),
                reorderLeadWeeks: $order->reorder_lead_weeks,
                consultantIds: $order->consultants->pluck('id')->all(),
                slowBurnPerWeek: $slow,
                fastBurnPerWeek: $fast,
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

    /**
     * Person-days per week of the burn rate window, one group of numbers per order.
     * Only weeks with bookings show up, BurnRate counts the others as 0.
     *
     * @return array<int, array<int, float>> order id => person-days of each week with bookings
     */
    private function weeklyBurn(CarbonImmutable $today): array
    {
        $start = BurnRate::windowStart($today)->toDateString();

        $rows = DB::table('bookings')
            ->selectRaw('order_id, FLOOR(DATEDIFF(worked_on, ?) / 7) AS week_no, SUM(hours) AS hours', [$start])
            ->where('status', '!=', BookingStatus::Rejected->value)
            ->whereBetween('worked_on', [$start, BurnRate::windowEnd($today)->toDateString()])
            ->groupBy('order_id', 'week_no')
            ->get();

        $perOrder = [];
        foreach ($rows as $row) {
            $perOrder[$row->order_id][] = (float) $row->hours / TimeBooking::HOURS_PER_PERSON_DAY;
        }

        return $perOrder;
    }

    /**
     * One order with its budget, forecast and the proposal for its follow-up order.
     *
     * @return array{order: Order, budget: OrderBudget, forecast: ForecastResult, proposal: FollowUpProposal}
     *
     * @throws NoProposalNeeded when the order does not need a follow-up order
     */
    public function proposalFor(Order $order, CarbonImmutable $today): array
    {
        $row = $this->rows($today, withForecast: true)->first(fn (array $row) => $row['order']->is($order));

        $proposal = FollowUpProposal::create(
            $row['forecast'],
            $this->workCalendar(),
            CarbonImmutable::parse($order->ends_on->toDateString()),
            $order->reorder_lead_weeks,
        );

        return $row + ['proposal' => $proposal];
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
