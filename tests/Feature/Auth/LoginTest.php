<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 段階 1：ログイン（F-19）。docs/test-viewpoints.md の TP-2-1〜TP-2-7。
 *
 * エラーメッセージの文言は FortifySetupTest が確かめているため、ここでは文言を直接書かない。
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_form_is_shown_to_guests(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    public function test_correct_credentials_log_in_and_redirect_to_book_list(): void
    {
        $user = User::factory()->create(['name' => '山田太郎', 'email' => 'yamada@example.com']);

        $this->post('/login', ['email' => 'yamada@example.com', 'password' => 'password'])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->get('/')->assertOk()->assertSee('山田太郎');
    }

    public function test_login_redirects_back_to_the_page_the_guest_tried_to_open(): void
    {
        User::factory()->create(['email' => 'yamada@example.com']);

        $this->get('/books/create')
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', url('/books/create'));

        $this->withSession(['url.intended' => url('/books/create')])
            ->post('/login', ['email' => 'yamada@example.com', 'password' => 'password'])
            ->assertRedirect(url('/books/create'));
    }

    public function test_empty_input_returns_with_errors_per_field(): void
    {
        $this->from(route('login'))
            ->post('/login', [])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_unregistered_email_and_wrong_password_show_the_same_message_on_email_field(): void
    {
        User::factory()->create(['email' => 'yamada@example.com']);

        $messageFor = function (string $email, string $password): string {
            $this->from(route('login'))
                ->post('/login', ['email' => $email, 'password' => $password])
                ->assertRedirect(route('login'))
                ->assertSessionHasErrors('email');

            return session('errors')->first('email');
        };

        $unregistered = $messageFor('nobody@example.com', 'password');
        $wrongPassword = $messageFor('yamada@example.com', 'wrong-password');

        $this->assertNotSame('', $unregistered);
        $this->assertSame($unregistered, $wrongPassword);
        $this->assertGuest();
    }

    public function test_login_is_limited_to_five_attempts_per_minute(): void
    {
        User::factory()->create(['email' => 'yamada@example.com']);

        collect(range(1, 5))->each(function (): void {
            $this->post('/login', ['email' => 'yamada@example.com', 'password' => 'wrong-password'])
                ->assertStatus(302);
        });

        $this->post('/login', ['email' => 'yamada@example.com', 'password' => 'wrong-password'])
            ->assertStatus(429);
    }

    public function test_successful_logins_also_count_toward_the_limit(): void
    {
        User::factory()->create(['email' => 'yamada@example.com']);

        collect(range(1, 5))->each(function (): void {
            $this->post('/login', ['email' => 'yamada@example.com', 'password' => 'password'])
                ->assertRedirect('/');
            auth()->logout();
        });

        $this->post('/login', ['email' => 'yamada@example.com', 'password' => 'password'])
            ->assertStatus(429);
    }

    public function test_logged_in_user_is_redirected_from_login_to_book_list(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect('/');
    }

    /**
     * ログインでも、メールアドレスの大文字小文字を区別しない（TP-2-7、Q-62）。
     */
    public function test_login_ignores_letter_case_of_email(): void
    {
        $user = User::factory()->create(['email' => 'yamada@example.com']);

        $this->post('/login', ['email' => 'YAMADA@Example.com', 'password' => 'password'])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }
}
