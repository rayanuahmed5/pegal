<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['holiday_on', 'name'])]
class PublicHoliday extends Model
{
    // the table has no created_at / updated_at, nobody cares when a holiday was added
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'holiday_on' => 'date',
        ];
    }
}
