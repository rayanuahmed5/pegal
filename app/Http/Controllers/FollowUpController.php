<?php

namespace App\Http\Controllers;

use App\Domain\NoProposalNeeded;
use App\Domain\OrderOverview;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// orders that need a follow-up order soon, for purchasing and the project lead
// (the routes keep everybody else out)
class FollowUpController extends Controller
{
    public function index(OrderOverview $overview): View
    {
        return view('follow-ups.index', ['rows' => $overview->followUps(CarbonImmutable::today())]);
    }

    // a draft of the follow-up order, only order-level numbers and never a person
    public function proposal(Order $order, OrderOverview $overview): View|RedirectResponse
    {
        $today = CarbonImmutable::today();

        try {
            $row = $overview->proposalFor($order, $today);
        } catch (NoProposalNeeded $e) {
            return redirect()->route('follow-ups.index')->withErrors(['proposal' => $e->getMessage()]);
        }

        return view('follow-ups.proposal', $row + ['today' => $today]);
    }
}
