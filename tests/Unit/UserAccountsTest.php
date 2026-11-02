<?php

namespace Tests\Unit;

use App\Domain\BookingStatus;
use App\Domain\InvalidUser;
use App\Domain\Role;
use App\Domain\UserAccounts;
use App\Models\Booking;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// creating accounts: a consultant needs a supplier, everybody else has none
class UserAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_consultant_with_a_supplier(): void
    {
        $supplier = Supplier::create(['name' => 'Test Consulting']);

        $user = (new UserAccounts())->create('Kofi Boateng', 'kofi@test.test', Role::Consultant, $supplier->id, 'a-long-password');

        $this->assertSame(Role::Consultant, $user->role);
        $this->assertSame($supplier->id, $user->supplier_id);
    }

    public function test_it_rejects_a_consultant_without_a_supplier(): void
    {
        $this->expectException(InvalidUser::class);
        $this->expectExceptionMessage('A consultant must belong to a supplier');

        (new UserAccounts())->create('Kofi Boateng', 'kofi@test.test', Role::Consultant, null, 'a-long-password');
    }

    public function test_it_rejects_a_consultant_with_a_supplier_that_does_not_exist(): void
    {
        $this->expectException(InvalidUser::class);

        (new UserAccounts())->create('Kofi Boateng', 'kofi@test.test', Role::Consultant, 999, 'a-long-password');
    }

    public function test_a_client_role_never_gets_a_supplier(): void
    {
        $supplier = Supplier::create(['name' => 'Test Consulting']);

        $user = (new UserAccounts())->create('Eva Finance', 'eva@test.test', Role::Finance, $supplier->id, 'a-long-password');

        $this->assertNull($user->supplier_id);
    }

    public function test_the_password_is_stored_hashed(): void
    {
        $user = (new UserAccounts())->create('Eva Finance', 'eva@test.test', Role::Finance, null, 'a-long-password');

        $this->assertNotSame('a-long-password', $user->password);
        $this->assertTrue(Hash::check('a-long-password', $user->password));
    }

    // the administrator changes an account

    public function test_it_updates_name_email_and_role(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $user = User::factory()->create(['role' => Role::Finance]);

        (new UserAccounts())->update($user, 'Eva Mensah', 'eva.m@test.test', Role::Purchasing, null, $admin);

        $user->refresh();
        $this->assertSame('Eva Mensah', $user->name);
        $this->assertSame(Role::Purchasing, $user->role);
    }

    public function test_it_rejects_an_administrator_changing_their_own_role(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->expectException(InvalidUser::class);
        $this->expectExceptionMessage('You cannot change your own role');

        (new UserAccounts())->update($admin, $admin->name, $admin->email, Role::Finance, null, $admin);
    }

    public function test_it_lets_an_administrator_change_their_own_name(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        (new UserAccounts())->update($admin, 'Ruth A.', $admin->email, Role::Admin, null, $admin);

        $this->assertSame('Ruth A.', $admin->fresh()->name);
    }

    public function test_it_rejects_a_new_role_for_a_consultant_with_bookings(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $consultant = $this->consultantWithBooking();

        $this->expectException(InvalidUser::class);
        $this->expectExceptionMessage('the role must stay Consultant');

        (new UserAccounts())->update($consultant, $consultant->name, $consultant->email, Role::Finance, null, $admin);
    }

    public function test_it_rejects_an_update_that_leaves_a_consultant_without_supplier(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $user = User::factory()->create(['role' => Role::Finance]);

        $this->expectException(InvalidUser::class);
        $this->expectExceptionMessage('A consultant must belong to a supplier');

        (new UserAccounts())->update($user, $user->name, $user->email, Role::Consultant, null, $admin);
    }

    public function test_reset_password_sets_the_new_password_without_the_old_one(): void
    {
        $user = User::factory()->create();

        (new UserAccounts())->resetPassword($user, 'set-by-the-admin');

        $this->assertTrue(Hash::check('set-by-the-admin', $user->fresh()->password));
    }

    // the administrator deletes an account

    public function test_it_deletes_an_account_without_bookings(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $user = User::factory()->create(['role' => Role::Finance]);

        (new UserAccounts())->delete($user, $admin);

        $this->assertModelMissing($user);
    }

    public function test_it_rejects_deleting_an_account_with_bookings(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $consultant = $this->consultantWithBooking();

        $this->expectException(InvalidUser::class);
        $this->expectExceptionMessage('has 1 booking. They are proof of work');

        (new UserAccounts())->delete($consultant, $admin);
    }

    public function test_it_rejects_an_administrator_deleting_themselves(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->expectException(InvalidUser::class);
        $this->expectExceptionMessage('You cannot delete your own account.');

        (new UserAccounts())->delete($admin, $admin);
    }

    private function consultantWithBooking(): User
    {
        $supplier = Supplier::create(['name' => 'Test Consulting']);
        $consultant = User::factory()->create(['name' => 'Ama Mensah', 'role' => Role::Consultant, 'supplier_id' => $supplier->id]);
        $order = Order::create([
            'number' => '4500000001',
            'title' => 'Test order',
            'supplier_id' => $supplier->id,
            'cost_center' => 'CC-1',
            'budget_pd' => 120,
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-12-31',
            'reorder_lead_weeks' => 4,
        ]);
        Booking::create([
            'order_id' => $order->id,
            'user_id' => $consultant->id,
            'worked_on' => '2026-10-05',
            'hours' => 8,
            'description' => 'Work',
            'status' => BookingStatus::Approved,
        ]);

        return $consultant;
    }

    // changing your own password

    public function test_it_changes_the_password_when_the_current_one_is_right(): void
    {
        $user = User::factory()->create(['password' => 'the-old-password']);

        (new UserAccounts())->changePassword($user, 'the-old-password', 'the-new-password');

        $this->assertTrue(Hash::check('the-new-password', $user->fresh()->password));
    }

    public function test_it_rejects_a_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => 'the-old-password']);

        $this->expectException(InvalidUser::class);
        $this->expectExceptionMessage('Your current password is not correct.');

        (new UserAccounts())->changePassword($user, 'a-wrong-guess', 'the-new-password');
    }

    public function test_it_rejects_a_new_password_that_is_the_same_as_the_old_one(): void
    {
        $user = User::factory()->create(['password' => 'the-old-password']);

        $this->expectException(InvalidUser::class);
        $this->expectExceptionMessage('must be different');

        (new UserAccounts())->changePassword($user, 'the-old-password', 'the-old-password');
    }
}
