<?php

namespace Tests\Feature;

use App\Domain\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_a_user_can_sign_in_with_the_right_password(): void
    {
        $user = User::factory()->create(['role' => Role::ProjectLead]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'not-the-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_each_role_sees_its_own_label_on_the_home_page(): void
    {
        foreach (Role::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/')
                ->assertOk()
                ->assertSee($role->label());
        }
    }

    public function test_signing_out_ends_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }
}
