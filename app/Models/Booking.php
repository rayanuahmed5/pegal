<?php

namespace App\Models;

use App\Domain\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'user_id', 'worked_on', 'starts_at', 'ends_at', 'hours', 'description', 'status', 'decided_by', 'decided_at', 'decision_reason'])]
class Booking extends Model
{
    protected function casts(): array
    {
        return [
            'worked_on' => 'date',
            'hours' => 'decimal:2',
            'status' => BookingStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // the consultant who did the work
    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // the finance user who approved or rejected the hours
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
