<?php

namespace Tests\Feature;

use App\Domain\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// the dark/light switch sits in the layout, so every page has it, signed in or not
class ThemeSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_sees_the_switch_on_the_landing_and_login_pages(): void
    {
        foreach (['/', '/login'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('id="theme-toggle"', false)
                ->assertSee('js/theme.js', false);
        }
    }

    public function test_every_signed_in_role_sees_the_switch(): void
    {
        foreach (Role::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/profile')
                ->assertOk()
                ->assertSee('id="theme-toggle"', false);
        }
    }

    public function test_the_first_choice_is_made_before_the_page_is_drawn(): void
    {
        // the early script in the head is what stops a white flash on a dark page
        $this->get('/login')->assertSee("localStorage.getItem('pegel-theme')", false);
    }

    public function test_the_stylesheet_has_a_dark_palette_and_the_script_exists(): void
    {
        $this->assertStringContainsString(':root[data-theme="dark"]', file_get_contents(public_path('css/pegal.css')));
        $this->assertFileExists(public_path('js/theme.js'));
    }
}
