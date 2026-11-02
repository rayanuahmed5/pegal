<?php

namespace App\Http\Controllers;

use App\Domain\FollowUp;
use App\Domain\OrderOverview;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

// orders that need a follow-up order soon, for purchasing and the project lead
// (the routes keep everybody else out)
class FollowUpController extends Controller
{
    public function index(OrderOverview $overview): View
    {
        $today = CarbonImmutable::today();

        $rows = $overview->rows($today, withForecast: true)
            ->map(fn (array $row) => $row + [
                'status' => FollowUp::status($row['forecast'], $today),
                'days' => FollowUp::daysUntilReorder($row['forecast'], $today),
            ])
            ->filter(fn (array $row) => $row['status'] !== null)
            // earliest reorder date first, so the overdue ones sit at the top
            ->sortBy(fn (array $row) => $row['forecast']->reorderDate->toDateString())
            ->values();

        return view('follow-ups.index', ['rows' => $rows]);
    }
}
