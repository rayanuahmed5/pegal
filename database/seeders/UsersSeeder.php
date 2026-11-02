<?php

namespace Database\Seeders;

use App\Domain\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    // same password for every demo account, they only exist for the demo
    private const DEMO_PASSWORD = 'pegal-demo';

    public function run(): void
    {
        $nordlicht = Supplier::where('name', 'Nordlicht Consulting')->firstOrFail();
        $bruckenwerk = Supplier::where('name', 'Brückenwerk Software')->firstOrFail();

        // one demo user per role, the README lists these
        $this->makeUser('Ama Mensah', 'consultant@pegal.test', Role::Consultant, $nordlicht);
        $this->makeUser('Katrin Weber', 'lead@pegal.test', Role::ProjectLead);
        $this->makeUser('Stefan Braun', 'purchasing@pegal.test', Role::Purchasing);
        $this->makeUser('Miriam Schulz', 'finance@pegal.test', Role::Finance);

        // three more consultants so the orders have a team
        $this->makeUser('Jonas Keller', 'jonas.keller@nordlicht.test', Role::Consultant, $nordlicht);
        $this->makeUser('Lena Vogt', 'lena.vogt@brueckenwerk.test', Role::Consultant, $bruckenwerk);
        $this->makeUser('Tariq Haddad', 'tariq.haddad@brueckenwerk.test', Role::Consultant, $bruckenwerk);
    }

    private function makeUser(string $name, string $email, Role $role, ?Supplier $supplier = null): void
    {
        User::create([
            'name' => $name,
            'email' => $email,
            'password' => self::DEMO_PASSWORD,
            'role' => $role,
            'supplier_id' => $supplier?->id,
        ]);
    }
}
