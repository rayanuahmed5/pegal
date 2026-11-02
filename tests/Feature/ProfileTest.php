<?php

namespace Tests\Feature;

use App\Domain\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// every signed-in user has a profile page and can change their own password
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public static function allRoles(): array
    {
        return array_map(fn (Role $role) => [$role], array_combine(
            array_map(fn (Role $role) => $role->value, Role::cases()),
            Role::cases(),
        ));
    }

    private function change(array $changes = []): array
    {
        return array_merge([
            'current_password' => 'password',
            'password' => 'a-new-long-password',
            'password_confirmation' => 'a-new-long-password',
        ], $changes);
    }

    public function test_a_guest_is_sent_to_the_login(): void
    {
        $this->get('/profile')->assertRedirect('/login');
        $this->put('/profile/password', $this->change())->assertRedirect('/login');
    }

    #[DataProvider('allRoles')]
    public function test_every_role_sees_its_own_profile(Role $role): void
    {
        $user = User::factory()->create(['name' => 'Ama Mensah', 'role' => $role]);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Ama Mensah')
            ->assertSee($user->email)
            ->assertSee($role->label());
    }

    public function test_a_user_can_change_their_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile/password', $this->change())
            ->assertRedirect('/profile')
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('a-new-long-password', $user->fresh()->password));
    }

    public function test_the_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile/password', $this->change(['current_password' => 'guessing']))
            ->assertSessionHasErrors(['current_password' => 'Your current password is not correct.']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_a_short_or_unconfirmed_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile/password', $this->change(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertSessionHasErrors('password');

        $this->actingAs($user)->put('/profile/password', $this->change(['password_confirmation' => 'something-else']))
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
