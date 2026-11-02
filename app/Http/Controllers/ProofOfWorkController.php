<?php

namespace App\Http\Controllers;

use App\Domain\BookingStatus;
use App\Domain\NothingToDecide;
use App\Domain\ProofOfWork;
use App\Domain\ReasonRequired;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// monthly proof of work for finance (the routes keep every other role out)
class ProofOfWorkController extends Controller
{
    public function index(Request $request, ProofOfWork $proofOfWork): View
    {
        $month = $this->monthFrom($request->query('month'));

        return view('proof-of-work.index', [
            'month' => $month,
            'previous' => $month->subMonth()->format('Y-m'),
            'next' => $month->addMonth()->format('Y-m'),
            'rows' => $proofOfWork->monthlyRows($month),
        ]);
    }

    public function decide(Request $request, ProofOfWork $proofOfWork): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'user_id' => ['required', 'exists:users,id'],
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'decision' => ['required', 'in:approve,reject'],
            // only a rejection needs words, an approval needs none
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $order = Order::findOrFail($data['order_id']);
        $consultant = User::findOrFail($data['user_id']);
        $month = $this->monthFrom($data['month']);
        $approve = $data['decision'] === 'approve';

        try {
            $count = $proofOfWork->decide(
                $order,
                $consultant,
                $month,
                $approve ? BookingStatus::Approved : BookingStatus::Rejected,
                $request->user(),
                $data['reason'] ?? null,
            );
        } catch (ReasonRequired $e) {
            return back()->withInput()->withErrors(['reason' => $e->getMessage()]);
        } catch (NothingToDecide $e) {
            return back()->withErrors(['decision' => $e->getMessage()]);
        }

        return redirect()
            ->route('proof-of-work.index', ['month' => $month->format('Y-m')])
            ->with('status', sprintf(
                '%s %d booking(s) of %s on order %s for %s.',
                $approve ? 'Approved' : 'Rejected',
                $count,
                $consultant->name,
                $order->number,
                $month->format('F Y'),
            ));
    }

    // "2026-10" becomes the 1st of that month, anything else means the current month
    private function monthFrom(mixed $value): CarbonImmutable
    {
        if (is_string($value) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            return CarbonImmutable::parse($value.'-01');
        }

        return CarbonImmutable::today()->startOfMonth();
    }
}
