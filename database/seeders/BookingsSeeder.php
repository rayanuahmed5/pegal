<?php

namespace Database\Seeders;

use App\Domain\BookingStatus;
use App\Models\Booking;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class BookingsSeeder extends Seeder
{
    // just something believable to put in the description field
    private const TASKS = [
        'Legacy data reconciliation',
        'API migration',
        'Test automation',
        'Release planning',
        'Bug fixing',
        'Code review',
    ];

    public function run(): void
    {
        $portal = Order::where('number', '4500012345')->firstOrFail();
        $warehouse = Order::where('number', '4500012390')->firstOrFail();
        $relaunch = Order::where('number', '4500012417')->firstOrFail();

        $ama = User::where('email', 'consultant@pegal.test')->firstOrFail();
        $jonas = User::where('email', 'jonas.keller@nordlicht.test')->firstOrFail();
        $lena = User::where('email', 'lena.vogt@brueckenwerk.test')->firstOrFail();
        $tariq = User::where('email', 'tariq.haddad@brueckenwerk.test')->firstOrFail();

        // two people, Monday to Wednesday, 14 weeks: 84 PD of 140 used
        $this->bookWeeks($portal, [$ama, $jonas], '2026-07-27', 14, [0, 1, 2]);

        // one person, Monday to Thursday, 8 weeks: 32 PD of 40 used
        $this->bookWeeks($warehouse, [$lena], '2026-09-07', 8, [0, 1, 2, 3]);

        // one person, full weeks, 4 weeks: 20 PD of 120 used
        $this->bookWeeks($relaunch, [$tariq], '2026-10-05', 4, [0, 1, 2, 3, 4]);
    }

    /**
     * Books 8 hours per person on the given weekdays (0 = Monday) for a number of weeks.
     *
     * @param  array<int, User>  $consultants
     * @param  array<int, int>  $weekdays
     */
    private function bookWeeks(Order $order, array $consultants, string $firstMonday, int $weeks, array $weekdays): void
    {
        $monday = CarbonImmutable::parse($firstMonday);
        $cutoff = CarbonImmutable::parse('2026-10-01');
        $count = 0;

        for ($week = 0; $week < $weeks; $week++) {
            foreach ($weekdays as $offset) {
                $day = $monday->addWeeks($week)->addDays($offset);

                foreach ($consultants as $consultant) {
                    Booking::create([
                        'order_id' => $order->id,
                        'user_id' => $consultant->id,
                        'worked_on' => $day,
                        'hours' => 8,
                        'description' => self::TASKS[$count % count(self::TASKS)],
                        // finance already signed off everything before October
                        'status' => $day->lt($cutoff) ? BookingStatus::Approved : BookingStatus::Submitted,
                    ]);
                    $count++;
                }
            }
        }
    }
}
