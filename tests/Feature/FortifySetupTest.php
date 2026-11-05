<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FortifySetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_and_timezone_are_japanese(): void
    {
        $this->assertSame('ja', config('app.locale'));
        $this->assertSame('Asia/Tokyo', config('app.timezone'));
    }

    public function test_redirect_after_authentication_is_top_page(): void
    {
        $this->assertSame('/', RouteServiceProvider::HOME);
        $this->assertSame('/', config('fortify.home'));
    }

    public function test_only_login_register_and_logout_routes_are_registered(): void
    {
        $this->assertTrue(Route::has(['login', 'register', 'logout']));

        collect(['password.request', 'password.update', 'verification.notice', 'user-profile-information.update', 'user-password.update', 'two-factor.login'])
            ->each(fn (string $name) => $this->assertFalse(Route::has($name), $name));
    }

    public function test_users_table_has_no_two_factor_columns(): void
    {
        $this->assertFalse(Schema::hasColumns('users', ['two_factor_secret']));
        $this->assertFalse(Schema::hasColumns('users', ['two_factor_recovery_codes']));
        $this->assertFalse(Schema::hasColumns('users', ['two_factor_confirmed_at']));
    }

    public function test_register_required_messages_are_japanese(): void
    {
        $errors = $this->registrationErrors([]);

        $this->assertSame(['お名前は必須です。'], $errors['name']);
        $this->assertSame(['メールアドレスは必須です。'], $errors['email']);
        $this->assertSame(['パスワードは必須です。'], $errors['password']);
    }

    public function test_register_format_messages_are_japanese(): void
    {
        $errors = $this->registrationErrors([
            'name' => str_repeat('あ', 256),
            'email' => 'not-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

        $this->assertContains('お名前は255文字以内で入力してください。', $errors['name']);
        $this->assertContains('メールアドレスは正しい形式で入力してください。', $errors['email']);
        $this->assertContains('パスワードは8文字以上で入力してください。', $errors['password']);
        $this->assertContains('パスワードとパスワード確認が一致しません。', $errors['password']);
    }

    public function test_register_duplicate_email_message_is_japanese(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $errors = $this->registrationErrors(['email' => 'taken@example.com']);

        $this->assertSame(['このメールアドレスはすでに登録されています。'], $errors['email']);
    }

    public function test_login_failure_messages_are_japanese(): void
    {
        $this->assertSame('メールアドレスまたはパスワードが正しくありません。', __('auth.failed'));
        $this->assertSame('ログインの試行回数が多すぎます。60秒後にもう一度お試しください。', __('auth.throttle', ['seconds' => 60]));
    }

    public function test_validation_summary_is_japanese(): void
    {
        $validator = Validator::make([], ['a' => 'required', 'b' => 'required', 'c' => 'required'], ['required' => '必須です。']);

        $this->assertSame('必須です。 （ほか2件のエラー）', (new ValidationException($validator))->getMessage());
    }

    /**
     * 会員登録の入力チェックを通し、項目ごとのエラーを返す。
     *
     * @param  array<string, string>  $input
     * @return array<string, array<int, string>>
     */
    private function registrationErrors(array $input): array
    {
        try {
            (new CreateNewUser)->create($input);
        } catch (ValidationException $e) {
            return $e->errors();
        }

        $this->fail('ValidationException が発生しませんでした。');
    }
}
