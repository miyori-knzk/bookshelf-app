<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 段階 1：ログアウト（F-20）。docs/test-viewpoints.md の TP-14-1〜TP-14-4。
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_invalidates_the_session(): void
    {
        $user = User::factory()->create();

        $this->withSession(['marker' => 'x']);
        $this->assertSame('x', session('marker'));

        // 印は、ふつうのリクエストでは残る。ログアウト（セッションの無効化）で消える。
        // セッション ID は、ログアウトをしなくてもリクエストごとに変わるため、比べない。
        $this->actingAs($user)
            ->post('/logout')
            ->assertSessionMissing('marker');
    }

    public function test_logout_redirects_to_login_and_logs_the_user_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
        $this->get('/login')
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    public function test_logout_does_not_change_the_users_table(): void
    {
        // 会員登録で作られたユーザーと同じく、remember_token が無い状態で確かめる
        $user = User::factory()->create(['remember_token' => null]);
        $before = User::all()->toArray();

        $this->actingAs($user)->post('/logout');

        $this->assertSame($before, User::all()->toArray());
    }

    public function test_get_request_to_logout_does_not_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/logout')
            ->assertStatus(405);

        $this->assertAuthenticated();
    }

    public function test_guest_posting_to_logout_is_redirected_to_login(): void
    {
        $this->post('/logout')->assertRedirect(route('login'));
    }

    public function test_favorites_page_redirects_to_login_after_logout(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout');

        $this->get('/favorites')->assertRedirect(route('login'));
    }
}
