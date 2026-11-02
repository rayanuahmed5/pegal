<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Supplier extends Model
{
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // the consultants who work for this firm
    public function consultants(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
