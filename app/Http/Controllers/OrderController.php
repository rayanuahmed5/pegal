<?php

namespace App\Http\Controllers;

use App\Domain\BookingStatus;
use App\Domain\BudgetForecast;
use App\Domain\BurnRate;
use App\Domain\OrderBudget;
use App\Domain\Role;
use App\Domain\TimeBooking;
use App\Domain\WorkCalendar;
use App\Models\Absence;
use App\Models\Order;
use App\Models\PublicHoliday;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

// budget per order for the client roles (the routes keep consultants out)
// only order-level numbers here, never a person, see docs/roles-and-privacy.md
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $today = CarbonImmutable::today();

        // forecast and reorder date are for project lead and purchasing only (role matrix),
        // so for finance it is not even calculated, not just hidden in the page
        $showForecast = in_array($request->user()->role, [Role::ProjectLead, Role::Purchasing], true);
        $forecaster = $showForecast ? new BudgetForecast($this->workCalendar()) : null;

        // one query for all orders, the used hours are summed in SQL
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

        $rows = $orders->map(function (Order $order) use ($forecaster, $today) {
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

        return view('orders.index', ['rows' => $rows, 'showForecast' => $showForecast]);
    }

    // holidays and vacations as plain arrays, the domain class has no idea about the database
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
