<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'number',
    'title',
    'supplier_id',
    'cost_center',
    'project_element',
    'budget_pd',
    'starts_on',
    'ends_on',
    'reorder_lead_weeks',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'budget_pd' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    // consultants who are allowed to book on this order (the assignments table)
    public function consultants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'assignments')->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
