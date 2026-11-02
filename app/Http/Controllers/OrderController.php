<?php

namespace App\Http\Controllers;

use App\Domain\OrderOverview;
use App\Domain\Role;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

// budget per order for the client roles (the routes keep consultants out)
// only order-level numbers here, never a person, see docs/roles-and-privacy.md
class OrderController extends Controller
{
    public function index(Request $request, OrderOverview $overview): View
    {
        // forecast and reorder date are for project lead and purchasing only (role matrix)
        $showForecast = in_array($request->user()->role, [Role::ProjectLead, Role::Purchasing], true);

        return view('orders.index', [
            'rows' => $overview->rows(CarbonImmutable::today(), $showForecast),
            'showForecast' => $showForecast,
        ]);
    }
}
