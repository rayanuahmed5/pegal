<?php

namespace Tests\Feature;

use App\Domain\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// the demo data is part of the product: one demo user per role, and seeding twice is harmless
class SeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_creates_one_demo_user_per_role(): void
    {
        $this->seed();

        foreach (Role::cases() as $role) {
            $this->assertTrue(User::where('role', $role->value)->exists(), "no demo user for {$role->value}");
        }

        foreach (['consultant@pegal.test', 'lead@pegal.test', 'purchasing@pegal.test', 'finance@pegal.test'] as $email) {
            $this->assertDatabaseHas('users', ['email' => $email]);
        }
    }

    public function test_seeding_twice_does_not_double_the_data(): void
    {
        $this->seed();
        $users = User::count();
        $bookings = \App\Models\Booking::count();

        $this->seed();

        $this->assertSame($users, User::count());
        $this->assertSame($bookings, \App\Models\Booking::count());
    }
}
