<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// the JSON shape of one order for the API (a DTO: what goes over the wire, kept apart from
// the Order model and the domain classes, so a change in the database does not break API users)
// only order-level numbers, never a person
class OrderBudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $row = $this->resource;
        $order = $row['order'];
        $budget = $row['budget'];
        $forecast = $row['forecast'];

        return [
            'number' => $order->number,
            'title' => $order->title,
            'supplier' => $order->supplier->name,
            'period' => [
                'from' => $order->starts_on->toDateString(),
                'to' => $order->ends_on->toDateString(),
            ],
            'budget' => [
                'orderedPd' => $budget->orderedPd,
                'usedPd' => round($budget->usedPd, 2),
                'remainingPd' => round($budget->remainingPd(), 2),
                'percentUsed' => $budget->percentUsed(),
                'level' => $budget->level(),
            ],
            // missing for finance, who is not allowed to see forecasts (role matrix)
            'forecast' => $forecast ? [
                'burnPerWeek' => round($forecast->burnPerWeek, 2),
                'exhaustionDate' => $forecast->exhaustionDate?->toDateString(),
                'exhaustionWeek' => $forecast->exhaustionWeek(),
                'reorderDate' => $forecast->reorderDate?->toDateString(),
                'reorderWeek' => $forecast->reorderWeek(),
                'missingPd' => $forecast->missingPd,
                'light' => $forecast->light,
                'assumptions' => $forecast->assumptions,
            ] : null,
            // only on the follow-up list
            'followUp' => isset($row['status']) ? [
                'status' => $row['status'],
                'daysUntilReorder' => $row['days'],
            ] : null,
        ];
    }
}
