<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Fills the database with the demo data.
     *
     * Safe to run again: if there are users already, nothing is added, so a
     * restart (or the Docker start script) never doubles the demo data.
     * For a clean start use: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        if (User::query()->exists()) {
            $this->command?->info('Demo data is already there, nothing seeded.');

            return;
        }

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
