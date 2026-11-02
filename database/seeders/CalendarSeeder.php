<?php

namespace Database\Seeders;

use App\Models\Absence;
use App\Models\PublicHoliday;
use App\Models\User;
use Illuminate\Database\Seeder;

class CalendarSeeder extends Seeder
{
    public function run(): void
    {
        // nationwide holidays only, state specific ones are out of scope for now
        $holidays = [
            '2026-12-25' => 'Christmas Day',
            '2026-12-26' => 'Second Day of Christmas',
            '2027-01-01' => "New Year's Day",
        ];

        foreach ($holidays as $date => $name) {
            PublicHoliday::create(['holiday_on' => $date, 'name' => $name]);
        }

        // two planned vacations so the forecast has something to work around
        $ama = User::where('email', 'consultant@pegal.test')->firstOrFail();
        $jonas = User::where('email', 'jonas.keller@nordlicht.test')->firstOrFail();

        Absence::create(['user_id' => $jonas->id, 'starts_on' => '2026-11-16', 'ends_on' => '2026-11-20']);
        Absence::create(['user_id' => $ama->id, 'starts_on' => '2026-12-21', 'ends_on' => '2027-01-01']);
    }
}
