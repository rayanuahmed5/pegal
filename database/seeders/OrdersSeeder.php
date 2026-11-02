<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrdersSeeder extends Seeder
{
    public function run(): void
    {
        $nordlicht = Supplier::where('name', 'Nordlicht Consulting')->firstOrFail();
        $bruckenwerk = Supplier::where('name', 'Brückenwerk Software')->firstOrFail();

        $ama = User::where('email', 'consultant@pegal.test')->firstOrFail();
        $jonas = User::where('email', 'jonas.keller@nordlicht.test')->firstOrFail();
        $lena = User::where('email', 'lena.vogt@brueckenwerk.test')->firstOrFail();
        $tariq = User::where('email', 'tariq.haddad@brueckenwerk.test')->firstOrFail();

        // the big one, budget lasts a while but not until the end (that's the point of the forecast)
        $portal = Order::create([
            'number' => '4500012345',
            'title' => 'Customer portal migration',
            'supplier_id' => $nordlicht->id,
            'cost_center' => 'CC-4711',
            'project_element' => 'P-2026-0815.03',
            'budget_pd' => 140,
            'starts_on' => '2026-07-27',
            'ends_on' => '2027-02-05',
            'reorder_lead_weeks' => 4,
        ]);
        $portal->consultants()->attach([$ama->id, $jonas->id]);

        // nearly used up, so the reorder date has already passed (should show red)
        $warehouse = Order::create([
            'number' => '4500012390',
            'title' => 'Data warehouse reporting',
            'supplier_id' => $bruckenwerk->id,
            'cost_center' => 'CC-4802',
            'project_element' => 'P-2026-0921.01',
            'budget_pd' => 40,
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-12-18',
            'reorder_lead_weeks' => 4,
        ]);
        $warehouse->consultants()->attach([$lena->id]);

        // just started, plenty of budget left (should show green)
        $relaunch = Order::create([
            'number' => '4500012417',
            'title' => 'Mobile app relaunch',
            'supplier_id' => $bruckenwerk->id,
            'cost_center' => 'CC-4933',
            'project_element' => 'P-2026-1005.02',
            'budget_pd' => 120,
            'starts_on' => '2026-10-05',
            'ends_on' => '2027-03-31',
            'reorder_lead_weeks' => 6,
        ]);
        $relaunch->consultants()->attach([$tariq->id]);
    }
}
