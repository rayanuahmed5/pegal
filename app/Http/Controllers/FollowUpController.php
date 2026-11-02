<?php

namespace App\Http\Controllers;

use App\Domain\OrderOverview;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

// orders that need a follow-up order soon, for purchasing and the project lead
// (the routes keep everybody else out)
class FollowUpController extends Controller
{
    public function index(OrderOverview $overview): View
    {
        return view('follow-ups.index', ['rows' => $overview->followUps(CarbonImmutable::today())]);
    }
}
