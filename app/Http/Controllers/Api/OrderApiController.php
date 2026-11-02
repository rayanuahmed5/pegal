<?php

namespace App\Http\Controllers\Api;

use App\Domain\OrderOverview;
use App\Domain\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderBudgetResource;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// the JSON side of the orders pages, same rules and same data as the HTML pages
class OrderApiController extends Controller
{
    public function index(Request $request, OrderOverview $overview): AnonymousResourceCollection
    {
        // forecast only for project lead and purchasing, like on the page
        $withForecast = in_array($request->user()->role, [Role::ProjectLead, Role::Purchasing], true);

        return OrderBudgetResource::collection($overview->rows(CarbonImmutable::today(), $withForecast));
    }

    public function followUps(OrderOverview $overview): AnonymousResourceCollection
    {
        return OrderBudgetResource::collection($overview->followUps(CarbonImmutable::today()));
    }
}
