<?php

namespace Tests\Unit;

use App\Domain\InvalidUser;
use App\Domain\Role;
use App\Domain\UserAccounts;
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
