<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Fills the database with the demo data.
     *
     * The seeders don't check for existing rows, so run it on a clean
     * database: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        // the order matters: users need suppliers, orders need users, and so on
        $this->call([
            SuppliersSeeder::class,
            UsersSeeder::class,
            OrdersSeeder::class,
            BookingsSeeder::class,
            CalendarSeeder::class,
        ]);
    }
}
