<?php

namespace Tests\Feature\Api;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * 段階 7：公開API のエラーの返し方（`docs/error-handling.md`「公開API の扱い」）。
 *
 * `/api/` の URL は、`Accept` ヘッダーが無くても JSON で返し、`error` を日本語にする（Q-53）。
 * Web のエラー画面は変えない。
 */
class ErrorResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_error_is_json_even_without_accept_header(): void
    {
        // `Accept` を付けない（`post()`）。Web なら入力画面へのリダイレクト（302）になるところ
        $response = $this->post('/api/v1/books', []);

        $response->assertStatus(422)->assertJsonStructure(['error', 'errors']);
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    public function test_not_found_is_json_even_without_accept_header(): void
    {
        $book = $this->get('/api/v1/books/9999');
        $path = $this->get('/api/v1/no-such-path');

        $book->assertNotFound();
        $path->assertNotFound();
        $this->assertStringContainsString('application/json', $book->headers->get('Content-Type'));
        $this->assertStringContainsString('application/json', $path->headers->get('Content-Type'));
    }

    public function test_not_found_messages_are_japanese_and_do_not_leak_the_model_class(): void
    {
        $book = $this->getJson('/api/v1/books/9999');
        $path = $this->getJson('/api/v1/no-such-path');

        $book->assertNotFound()->assertExactJson(['error' => '書籍が見つかりません。']);
        $path->assertNotFound()->assertExactJson(['error' => '指定されたURLは存在しません。']);
        $this->assertStringNotContainsString('App\\Models', $book->getContent());
        $this->assertStringNotContainsString('App\\\\Models', $book->getContent());
    }

    public function test_method_not_allowed_message_is_japanese(): void
    {
        // `/api/v1/books` は、GET と POST だけ。DELETE は 405 になる
        $response = $this->deleteJson('/api/v1/books');

        $response->assertStatus(405)->assertExactJson(['error' => 'このメソッドには対応していません。']);
        $this->assertNotEmpty($response->headers->get('Allow'));
    }

    public function test_validation_message_shows_first_error_and_the_rest_in_japanese(): void
    {
        // 登録者ID・タイトル・著者・ISBN・出版日・ジャンルの 6 件。最初は `rules()` に書いた順の最初
        $response = $this->postJson('/api/v1/books', []);

        $response->assertStatus(422);
        $this->assertSame('登録者IDは必須です。 （ほか5件のエラー）', $response->json('error'));
        $this->assertStringNotContainsString('more error', $response->json('error'));
    }

    public function test_validation_message_has_no_count_when_there_is_one_error(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->postJson('/api/v1/books', [
            'user_id' => $user->id,
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '123',
            'published_date' => '2012-06-23',
            'genres' => [$genre->id],
        ]);

        $response->assertStatus(422)->assertJsonPath('error', 'ISBNは13桁の数字で入力してください。');
    }

    public function test_rate_limit_allows_sixty_requests_per_minute_and_stops_the_sixty_first(): void
    {
        collect(range(1, 60))->each(fn () => $this->getJson('/api/v1/books')->assertOk());

        $this->getJson('/api/v1/books')
            ->assertStatus(429)
            ->assertExactJson(['error' => 'リクエストが多すぎます。しばらくしてからもう一度お試しください。'])
            ->assertHeader('Retry-After');
    }

    public function test_unexpected_error_returns_only_a_japanese_message_when_debug_is_off(): void
    {
        config(['app.debug' => false, 'logging.default' => 'null']);
        Route::get('/api/v1/test-error', fn () => throw new \RuntimeException('秘密の内部情報 SELECT * FROM users'));

        $response = $this->getJson('/api/v1/test-error');

        $response->assertStatus(500)->assertExactJson(['error' => 'サーバーでエラーが発生しました。']);
        $this->assertStringNotContainsString('秘密の内部情報', $response->getContent());
        $this->assertStringNotContainsString('SELECT', $response->getContent());
    }

    public function test_unexpected_error_is_json_even_without_accept_header(): void
    {
        config(['app.debug' => false, 'logging.default' => 'null']);
        Route::get('/api/v1/test-error', fn () => throw new \RuntimeException('boom'));

        $response = $this->get('/api/v1/test-error');

        $response->assertStatus(500)->assertExactJson(['error' => 'サーバーでエラーが発生しました。']);
    }

    public function test_error_messages_are_not_unicode_escaped(): void
    {
        // 日本語が `\u5c71`（「山」の文字コード）のような記号にならず、そのまま入る（Q-74）。ルートが無い 404 や、`api` グループの外のエラーも対象
        $responses = [
            $this->get('/api/v1/books/9999'),
            $this->get('/api/v1/no-such-path'),
            $this->delete('/api/v1/books'),
            $this->post('/api/v1/books', []),
        ];

        $this->assertStringContainsString('書籍が見つかりません。', $responses[0]->getContent());
        $this->assertStringContainsString('指定されたURLは存在しません。', $responses[1]->getContent());
        $this->assertStringContainsString('このメソッドには対応していません。', $responses[2]->getContent());
        $this->assertStringContainsString('タイトル', $responses[3]->getContent());
        collect($responses)->each(fn ($response) => $this->assertStringNotContainsString('\u', $response->getContent()));
    }

    public function test_unicode_escaping_is_only_turned_off_for_api_urls(): void
    {
        // `/api/` の外の JSON は、これまでどおりエスケープされる
        Route::get('/test-json', fn () => response()->json(['message' => '山田']));

        $content = $this->get('/test-json')->getContent();

        $this->assertStringContainsString('\\u5c71\\u7530', $content);
        $this->assertStringNotContainsString('山田', $content);
    }

    public function test_web_error_pages_are_not_changed(): void
    {
        // Web の 404 は JSON にならず、公開API の文言も入らない
        $response = $this->get('/no-such-page');

        $response->assertNotFound();
        $this->assertStringNotContainsString('application/json', (string) $response->headers->get('Content-Type'));
        $this->assertStringNotContainsString('指定されたURLは存在しません。', $response->getContent());
    }

    public function test_web_validation_error_still_redirects_back(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/books/create')
            ->post('/books', [])
            ->assertRedirect('/books/create')
            ->assertSessionHasErrors('title');
    }
}
