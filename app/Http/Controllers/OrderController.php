<?php

namespace App\Http\Controllers;

use App\Domain\BookingStatus;
use App\Domain\OrderBudget;
use App\Models\Order;
use Illuminate\View\View;

// budget per order for the client roles (the routes keep consultants out)
// only order-level numbers here, never a person, see docs/roles-and-privacy.md
class OrderController extends Controller
{
    public function index(): View
    {
        // one query for all orders, the used hours are summed in SQL
        $orders = Order::query()
            ->with('supplier')
            ->withSum(
                ['bookings as used_hours' => fn ($q) => $q->where('status', '!=', BookingStatus::Rejected->value)],
                'hours',
            )
            ->orderBy('number')
            ->get();

        $rows = $orders->map(fn (Order $order) => [
            'order' => $order,
            'budget' => OrderBudget::fromHours((float) $order->budget_pd, (float) $order->used_hours),
        ]);

        return view('orders.index', ['rows' => $rows]);
    }
}
