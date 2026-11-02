<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SuppliersSeeder extends Seeder
{
    public function run(): void
    {
        // two made-up consulting firms, enough to see more than one supplier in the demo
        Supplier::create(['name' => 'Nordlicht Consulting']);
        Supplier::create(['name' => 'Brückenwerk Software']);
    }
}
