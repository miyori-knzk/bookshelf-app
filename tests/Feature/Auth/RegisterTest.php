<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * 段階 1：会員登録（F-18）。docs/test-viewpoints.md の TP-1-1〜TP-1-12。
 *
 * エラーメッセージの文言は FortifySetupTest が確かめているため、ここでは文言を直接書かず、
 * エラーが付く項目と、users の行の増減で確かめる。
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正しい入力。必要な項目だけ上書きして使う。
     *
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'name' => '山田花子',
            'email' => 'hanako@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    /**
     * 会員登録画面から送信する（エラーのときは会員登録画面へ戻る）。
     *
     * @param  array<string, string>  $input
     */
    private function submit(array $input): TestResponse
    {
        return $this->from(route('register'))->post(route('register.store'), $input);
    }

    public function test_register_form_is_shown_to_guests(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_register_page_links_to_login(): void
    {
        $this->get('/register')->assertSee('href="' . route('login') . '"', false);
    }

    public function test_valid_input_creates_user_and_redirects_to_book_list_as_logged_in(): void
    {
        $this->submit($this->validInput())->assertRedirect('/');

        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticated();
        $this->get('/')->assertOk()->assertSee('山田花子');
    }

    public function test_password_is_stored_hashed(): void
    {
        $this->submit($this->validInput());

        $user = User::firstOrFail();

        $this->assertNotSame('password', $user->password);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_empty_input_returns_to_form_with_errors_per_field(): void
    {
        $this->submit([])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_invalid_email_format_is_rejected(): void
    {
        $this->submit($this->validInput(['email' => 'not-an-email']))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registered_email_is_rejected_even_if_only_letter_case_differs(): void
    {
        User::factory()->create(['email' => 'yamada@example.com']);

        collect(['yamada@example.com', 'YAMADA@example.com'])->each(function (string $email): void {
            $this->submit($this->validInput(['email' => $email]))
                ->assertRedirect(route('register'))
                ->assertSessionHasErrors('email');
        });

        $this->assertDatabaseCount('users', 1);
    }

    /**
     * 大文字を含むメールアドレスも登録でき、小文字にそろえて保存される（TP-1-12、Q-62）。
     */
    public function test_email_with_uppercase_letters_can_be_registered_and_is_saved_in_lowercase(): void
    {
        $this->submit($this->validInput(['email' => 'Hanako@Example.com']))->assertRedirect('/');

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('hanako@example.com', User::firstOrFail()->email);
    }

    public function test_password_confirmation_mismatch_is_rejected_on_password_field(): void
    {
        $this->submit($this->validInput(['password_confirmation' => 'different']))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('password')
            ->assertSessionDoesntHaveErrors('password_confirmation');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_name_boundary_255_passes_and_256_fails(): void
    {
        $this->submit($this->validInput(['name' => str_repeat('あ', 255)]))->assertRedirect('/');
        $this->assertDatabaseCount('users', 1);

        auth()->logout();

        $this->submit($this->validInput(['name' => str_repeat('あ', 256), 'email' => 'other@example.com']))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('name');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_email_boundary_255_passes_and_256_fails(): void
    {
        $this->submit($this->validInput(['email' => $this->emailOfLength(255)]))->assertRedirect('/');
        $this->assertDatabaseCount('users', 1);

        auth()->logout();

        $this->submit($this->validInput(['email' => $this->emailOfLength(256)]))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_password_boundary_8_passes_and_7_fails(): void
    {
        $this->submit($this->validInput(['password' => str_repeat('a', 7), 'password_confirmation' => str_repeat('a', 7)]))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);

        $this->submit($this->validInput(['password' => str_repeat('a', 8), 'password_confirmation' => str_repeat('a', 8)]))
            ->assertRedirect('/');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_name_and_email_are_kept_but_password_is_not_when_returning_with_errors(): void
    {
        $this->followingRedirects()
            ->submit($this->validInput(['email' => 'not-an-email', 'password' => 'secret-pass', 'password_confirmation' => 'other-pass']))
            ->assertSee('value="山田花子"', false)
            ->assertSee('value="not-an-email"', false)
            ->assertDontSee('secret-pass')
            ->assertDontSee('other-pass');
    }

    public function test_logged_in_user_is_redirected_from_register_to_book_list(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/register')
            ->assertRedirect('/');
    }

    /**
     * 開こうとした画面が保存されているときは、会員登録の後もその画面へ移動する（TP-1-11、Q-61）。
     */
    public function test_after_register_goes_to_the_page_saved_for_return(): void
    {
        $this->withSession(['url.intended' => url('/books/create')])
            ->submit($this->validInput())
            ->assertRedirect(url('/books/create'));
    }

    /**
     * 未ログインで「書籍登録」を開き、ログイン画面から会員登録の画面へ移っても、開こうとした画面は残っている（AC-1-6、Q-61）。
     */
    public function test_saved_page_survives_moving_from_login_to_register(): void
    {
        $this->get('/books/create')->assertRedirect(route('login'));
        $this->get('/register')->assertOk();

        $this->submit($this->validInput())->assertRedirect(url('/books/create'));
    }

    /**
     * 指定した文字数の、形式が正しいメールアドレスを作る。
     *
     * ローカル部は 64 文字、ドメインは 40 文字以内のラベルをつなぐ。
     * （63 文字のラベルを 2 つ続けると、長さに関係なく `email` ルールの形式エラーになるため、短くしている。）
     */
    private function emailOfLength(int $length): string
    {
        $remaining = $length - 64 - 1;
        $labels = [];

        while ($remaining > 41) {
            $labels[] = str_repeat('b', 40);
            $remaining -= 41;
        }

        $labels[] = str_repeat('b', $remaining);

        return str_repeat('a', 64) . '@' . implode('.', $labels);
    }
}
