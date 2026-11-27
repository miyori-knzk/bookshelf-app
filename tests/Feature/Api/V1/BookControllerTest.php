<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 段階 7：公開API の書籍の一覧・詳細・登録・更新・削除（F-21〜F-25）。
 *
 * エラーの形（`Accept` が無いとき、404・405・429・500 の `error`）は ErrorResponseTest で確かめる。
 */
class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Genre $genre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => '山田太郎']);
        $this->genre = Genre::factory()->create(['name' => '小説']);
    }

    /**
     * 登録・更新で通る入力。上書きしたい項目だけ渡す。
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validInput(array $overrides = []): array
    {
        return [
            'user_id' => $this->user->id,
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_date' => '2012-06-23',
            'description' => null,
            'image_url' => null,
            'genres' => [$this->genre->id],
            ...$overrides,
        ];
    }

    /**
     * ジャンルを 1 つ付けた書籍を作る。
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createBook(array $attributes = [], ?Genre $genre = null): Book
    {
        $book = Book::factory()->create(['user_id' => $this->user->id, ...$attributes]);
        $book->genres()->attach(($genre ?? $this->genre)->id);

        return $book;
    }

    /**
     * リクエストの間に発行された SQL の数を返す。
     */
    private function countQueries(callable $request): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $request();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    // ---- 書籍一覧（F-21）----

    public function test_index_returns_data_links_and_meta_with_twenty_per_page_by_default(): void
    {
        Book::factory()->count(21)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/v1/books');

        $response->assertOk()
            ->assertJsonStructure(['data', 'links' => ['first', 'last', 'prev', 'next'], 'meta' => ['current_page', 'per_page', 'total', 'last_page']])
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 21)
            ->assertJsonPath('meta.last_page', 2);
        $this->getJson('/api/v1/books?page=2')->assertJsonCount(1, 'data');
    }

    public function test_index_book_object_has_the_documented_fields_and_types(): void
    {
        $this->createBook([
            'isbn' => '0123456789012',
            'published_date' => '2012-06-23',
            'description' => null,
            'image_url' => null,
            'created_at' => '2026-11-01 10:00:00',
            'updated_at' => '2026-11-01 10:00:00',
        ]);

        $book = $this->getJson('/api/v1/books')->assertOk()->json('data.0');

        $this->assertSame(
            ['id', 'title', 'author', 'isbn', 'published_date', 'description', 'image_url', 'user', 'genres',
                'reviews_avg_rating', 'reviews_count', 'created_at', 'updated_at'],
            array_keys($book),
        );
        $this->assertSame('0123456789012', $book['isbn']);
        $this->assertSame('2012-06-23', $book['published_date']);
        $this->assertNull($book['description']);
        $this->assertNull($book['image_url']);
        // アプリのタイムゾーンは日本時間だが、日時は UTC で返す（Q-51）
        $this->assertSame('2026-11-01T01:00:00.000000Z', $book['created_at']);
        $this->assertSame('2026-11-01T01:00:00.000000Z', $book['updated_at']);
        $this->assertSame([['id' => $this->genre->id, 'name' => '小説']], $book['genres']);
    }

    public function test_published_date_is_null_when_missing(): void
    {
        $book = $this->createBook(['published_date' => null]);

        $this->getJson('/api/v1/books')->assertOk()->assertJsonPath('data.0.published_date', null);
        $this->getJson("/api/v1/books/{$book->id}")->assertOk()->assertJsonPath('data.published_date', null);
    }

    public function test_index_user_has_only_id_and_name(): void
    {
        $this->createBook();

        $user = $this->getJson('/api/v1/books')->json('data.0.user');

        $this->assertSame(['id' => $this->user->id, 'name' => '山田太郎'], $user);
        $this->assertStringNotContainsString($this->user->email, $this->getJson('/api/v1/books')->getContent());
    }

    public function test_index_orders_by_created_at_desc_then_id_desc(): void
    {
        $older = $this->createBook(['created_at' => '2026-11-01 10:00:00']);
        $sameA = $this->createBook(['created_at' => '2026-11-02 10:00:00']);
        $sameB = $this->createBook(['created_at' => '2026-11-02 10:00:00']);

        $ids = $this->getJson('/api/v1/books')->json('data.*.id');

        $this->assertSame([$sameB->id, $sameA->id, $older->id], $ids);
    }

    public function test_index_keyword_matches_title_or_author_partially(): void
    {
        $title = $this->createBook(['title' => '吾輩は猫である', 'author' => '夏目漱石']);
        $author = $this->createBook(['title' => 'こころ', 'author' => '夏目漱石']);
        $this->createBook(['title' => '人を動かす', 'author' => 'D・カーネギー']);

        $byTitle = $this->getJson('/api/v1/books?keyword=猫')->json('data.*.id');
        $byAuthor = $this->getJson('/api/v1/books?keyword=漱石')->json('data.*.id');

        $this->assertSame([$title->id], $byTitle);
        $this->assertSame([$author->id, $title->id], $byAuthor);
    }

    public function test_index_genre_id_filters_and_combines_with_keyword(): void
    {
        $other = Genre::factory()->create(['name' => 'ビジネス']);
        $novelSoseki = $this->createBook(['title' => '吾輩は猫である', 'author' => '夏目漱石']);
        $this->createBook(['title' => '坊っちゃん', 'author' => '夏目漱石'], $other);
        $novelOther = $this->createBook(['title' => '人間失格', 'author' => '太宰治']);

        $byGenre = $this->getJson("/api/v1/books?genre_id={$this->genre->id}")->json('data.*.id');
        $both = $this->getJson("/api/v1/books?genre_id={$this->genre->id}&keyword=夏目")->json('data.*.id');

        $this->assertEqualsCanonicalizing([$novelSoseki->id, $novelOther->id], $byGenre);
        $this->assertSame([$novelSoseki->id], $both);
    }

    public function test_index_treats_percent_underscore_and_backslash_in_keyword_as_plain_characters(): void
    {
        $percent = $this->createBook(['title' => '100%の本']);
        $underscore = $this->createBook(['title' => 'a_b']);
        $backslash = $this->createBook(['title' => 'a\\b']);
        $this->createBook(['title' => '100点の本']);
        $this->createBook(['title' => 'axb']);

        $this->assertSame([$percent->id], $this->getJson('/api/v1/books?keyword=%25')->json('data.*.id'));
        $this->assertSame([$underscore->id], $this->getJson('/api/v1/books?keyword=a_b')->json('data.*.id'));
        $this->assertSame([$backslash->id], $this->getJson('/api/v1/books?keyword=' . urlencode('a\\b'))->json('data.*.id'));
    }

    public function test_index_returns_empty_data_with_200_when_nothing_matches(): void
    {
        $this->createBook(['title' => '吾輩は猫である']);

        $this->getJson('/api/v1/books?keyword=存在しない')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);
    }

    public function test_index_treats_empty_values_as_not_specified(): void
    {
        $this->createBook();
        $this->createBook();

        $this->getJson('/api/v1/books?keyword=&genre_id=&page=&per_page=')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 20);
    }

    /**
     * @dataProvider perPageProvider
     */
    public function test_index_per_page_boundaries(string $value, int $status): void
    {
        $this->getJson('/api/v1/books?per_page=' . $value)->assertStatus($status);
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function perPageProvider(): array
    {
        return [
            '下限 1' => ['1', 200],
            '上限 100' => ['100', 200],
            '0 は範囲外' => ['0', 422],
            '101 は範囲外' => ['101', 422],
            '整数ではない' => ['abc', 422],
        ];
    }

    public function test_index_per_page_error_message_is_japanese(): void
    {
        $this->getJson('/api/v1/books?per_page=101')
            ->assertStatus(422)
            ->assertJsonPath('errors.per_page', ['ページあたり件数は1〜100で指定してください。']);
    }

    public function test_index_page_boundaries(): void
    {
        $this->getJson('/api/v1/books?page=1')->assertOk();
        $this->getJson('/api/v1/books?page=0')->assertStatus(422)
            ->assertJsonPath('errors.page', ['ページ番号は1以上で指定してください。']);
        $this->getJson('/api/v1/books?page=abc')->assertStatus(422)
            ->assertJsonPath('errors.page', ['ページ番号は整数で指定してください。']);
    }

    public function test_index_keyword_length_boundary(): void
    {
        $this->getJson('/api/v1/books?keyword=' . str_repeat('あ', 255))->assertOk();
        $this->getJson('/api/v1/books?keyword=' . str_repeat('あ', 256))->assertStatus(422)
            ->assertJsonPath('errors.keyword', ['キーワードは255文字以内で指定してください。']);
    }

    public function test_index_rejects_unknown_or_malformed_genre_id(): void
    {
        $this->getJson('/api/v1/books?genre_id=9999')->assertStatus(422)
            ->assertJsonPath('errors.genre_id', ['指定したジャンルは存在しません。']);
        $this->getJson('/api/v1/books?genre_id=abc')->assertStatus(422)
            ->assertJsonPath('errors.genre_id', ['ジャンルIDは整数で指定してください。']);
    }

    public function test_index_links_keep_keyword_genre_id_and_per_page(): void
    {
        Book::factory()->count(3)->create(['user_id' => $this->user->id])
            ->each(fn (Book $book) => $book->genres()->attach($this->genre->id));

        $next = $this->getJson("/api/v1/books?keyword=&genre_id={$this->genre->id}&per_page=2")
            ->assertOk()
            ->json('links.next');

        parse_str((string) parse_url($next, PHP_URL_QUERY), $query);
        $this->assertSame(['genre_id' => (string) $this->genre->id, 'per_page' => '2', 'page' => '2'], $query);
        // 次のページをたどっても、同じ条件の続きが返る
        $this->getJson($next)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 3);
    }

    public function test_index_shows_average_rating_rounded_to_one_decimal_and_review_count(): void
    {
        $five = $this->createBook();
        $third = $this->createBook();
        collect([5, 4])->each(fn (int $rating) => Review::factory()->create(['book_id' => $five->id, 'rating' => $rating]));
        collect([4, 4, 5])->each(fn (int $rating) => Review::factory()->create(['book_id' => $third->id, 'rating' => $rating]));

        $books = collect($this->getJson('/api/v1/books')->json('data'))->keyBy('id');

        $this->assertSame(4.5, $books[$five->id]['reviews_avg_rating']);
        $this->assertSame(2, $books[$five->id]['reviews_count']);
        // 4.333… は小数第 1 位に丸める
        $this->assertSame(4.3, $books[$third->id]['reviews_avg_rating']);
        $this->assertSame(3, $books[$third->id]['reviews_count']);
    }

    public function test_index_book_without_reviews_has_null_average_and_zero_count(): void
    {
        $this->createBook();

        $this->getJson('/api/v1/books')
            ->assertJsonPath('data.0.reviews_avg_rating', null)
            ->assertJsonPath('data.0.reviews_count', 0);
    }

    public function test_index_does_not_include_reviews(): void
    {
        $book = $this->createBook();
        Review::factory()->create(['book_id' => $book->id]);

        $this->getJson('/api/v1/books')->assertJsonMissingPath('data.0.reviews');
    }

    public function test_index_query_count_does_not_grow_with_book_count(): void
    {
        Book::factory()->count(3)->create(['user_id' => $this->user->id])
            ->each(fn (Book $book) => $book->genres()->attach($this->genre->id));
        $few = $this->countQueries(fn () => $this->getJson('/api/v1/books')->assertOk());

        Book::factory()->count(9)->create(['user_id' => $this->user->id])
            ->each(fn (Book $book) => $book->genres()->attach($this->genre->id));
        $many = $this->countQueries(fn () => $this->getJson('/api/v1/books')->assertOk());

        $this->assertSame($few, $many);
    }

    // ---- 書籍詳細（F-22）----

    public function test_show_returns_the_book_with_all_reviews_newest_first(): void
    {
        $book = $this->createBook();
        $reviewer = User::factory()->create(['name' => '鈴木花子']);
        $old = Review::factory()->create(['book_id' => $book->id, 'user_id' => $reviewer->id, 'rating' => 5, 'comment' => '古い', 'created_at' => '2026-11-01 10:00:00']);
        $newA = Review::factory()->create(['book_id' => $book->id, 'user_id' => $reviewer->id, 'rating' => 3, 'comment' => '新しい', 'created_at' => '2026-11-02 10:00:00']);
        $newB = Review::factory()->create(['book_id' => $book->id, 'user_id' => $reviewer->id, 'rating' => 4, 'comment' => '同じ日時', 'created_at' => '2026-11-02 10:00:00']);

        $response = $this->getJson("/api/v1/books/{$book->id}")->assertOk();

        $this->assertSame([$newB->id, $newA->id, $old->id], $response->json('data.reviews.*.id'));
        $this->assertSame(
            ['id', 'rating', 'comment', 'user', 'created_at'],
            array_keys($response->json('data.reviews.0')),
        );
        $this->assertSame(['id' => $reviewer->id, 'name' => '鈴木花子'], $response->json('data.reviews.0.user'));
        // JSON では 4.0 が `4` と出力される（PHP の json_encode の既定）ため、値だけを比べる
        $this->assertEquals(4, $response->json('data.reviews_avg_rating'));
        $this->assertSame(3, $response->json('data.reviews_count'));
        $this->assertStringNotContainsString($reviewer->email, $response->getContent());
        $this->assertStringNotContainsString($this->user->email, $response->getContent());
    }

    public function test_show_book_without_reviews_has_empty_reviews_null_average_and_zero_count(): void
    {
        $book = $this->createBook();

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.reviews', [])
            ->assertJsonPath('data.reviews_avg_rating', null)
            ->assertJsonPath('data.reviews_count', 0);
    }

    public function test_show_returns_404_json_for_missing_book(): void
    {
        $this->getJson('/api/v1/books/9999')
            ->assertNotFound()
            ->assertExactJson(['error' => '書籍が見つかりません。']);
    }

    public function test_japanese_is_not_unicode_escaped_in_responses(): void
    {
        // 一覧・詳細・登録の応答の日本語が、`\u5c71`（「山」の文字コード）のような記号にならず、そのまま入る（Q-74）
        $book = $this->createBook(['title' => '吾輩は猫である']);

        $index = $this->getJson('/api/v1/books')->assertOk();
        $show = $this->getJson("/api/v1/books/{$book->id}")->assertOk();
        $store = $this->postJson('/api/v1/books', $this->validInput(['title' => '坊っちゃん']))->assertCreated();

        $this->assertStringContainsString('吾輩は猫である', $index->getContent());
        $this->assertStringContainsString('吾輩は猫である', $show->getContent());
        $this->assertStringContainsString('坊っちゃん', $store->getContent());
        collect([$index, $show, $store])->each(fn ($response) => $this->assertStringNotContainsString('\u', $response->getContent()));
    }

    public function test_show_query_count_does_not_grow_with_review_count(): void
    {
        $book = $this->createBook();
        Review::factory()->count(2)->create(['book_id' => $book->id]);
        $few = $this->countQueries(fn () => $this->getJson("/api/v1/books/{$book->id}")->assertOk());

        Review::factory()->count(8)->create(['book_id' => $book->id]);
        $many = $this->countQueries(fn () => $this->getJson("/api/v1/books/{$book->id}")->assertOk());

        $this->assertSame($few, $many);
    }

    // ---- 書籍登録（F-23）----

    public function test_store_creates_book_with_genres_and_returns_201_in_the_detail_shape(): void
    {
        $registrant = User::factory()->create(['name' => '鈴木花子']);

        $response = $this->postJson('/api/v1/books', $this->validInput(['user_id' => $registrant->id]));

        $response->assertCreated()
            ->assertJsonPath('data.title', 'リーダブルコード')
            ->assertJsonPath('data.user', ['id' => $registrant->id, 'name' => '鈴木花子'])
            ->assertJsonPath('data.genres', [['id' => $this->genre->id, 'name' => '小説']])
            ->assertJsonPath('data.reviews', [])
            ->assertJsonPath('data.reviews_avg_rating', null)
            ->assertJsonPath('data.reviews_count', 0);
        $book = Book::firstOrFail();
        $this->assertSame($registrant->id, $book->user_id);
        $this->assertSame($book->id, $response->json('data.id'));
        $this->assertSame([$this->genre->id], $book->genres()->pluck('genres.id')->all());
    }

    public function test_store_needs_neither_login_nor_csrf_token(): void
    {
        $this->assertGuest();

        $this->postJson('/api/v1/books', $this->validInput())->assertCreated();
        $this->post('/api/v1/books', $this->validInput(['isbn' => '9784873115641']), ['Accept' => 'application/json'])->assertCreated();
    }

    public function test_store_requires_an_existing_user_id(): void
    {
        $input = $this->validInput();
        unset($input['user_id']);

        $this->postJson('/api/v1/books', $input)
            ->assertStatus(422)
            ->assertJsonPath('errors.user_id', ['登録者IDは必須です。']);
        $this->postJson('/api/v1/books', $this->validInput(['user_id' => 9999]))
            ->assertStatus(422)
            ->assertJsonPath('errors.user_id', ['登録者が存在しません。']);
        $this->postJson('/api/v1/books', $this->validInput(['user_id' => 'abc']))
            ->assertStatus(422)
            ->assertJsonPath('errors.user_id', ['登録者IDは整数で指定してください。']);
        $this->assertSame(0, Book::count());
    }

    public function test_store_returns_japanese_errors_for_every_failing_field(): void
    {
        $response = $this->postJson('/api/v1/books', ['user_id' => $this->user->id]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.title', ['タイトルは必須です。'])
            ->assertJsonPath('errors.author', ['著者は必須です。'])
            ->assertJsonPath('errors.isbn', ['ISBNは必須です。'])
            ->assertJsonPath('errors.published_date', ['出版日は必須です。'])
            ->assertJsonPath('errors.genres', ['ジャンルを1つ以上選択してください。']);
        $this->assertSame(0, Book::count());
    }

    public function test_store_applies_the_same_rules_as_the_web_form(): void
    {
        Book::factory()->create(['user_id' => $this->user->id, 'isbn' => '9784873115658']);

        $this->postJson('/api/v1/books', $this->validInput())
            ->assertStatus(422)
            ->assertJsonPath('errors.isbn', ['このISBNはすでに登録されています。']);
        $this->postJson('/api/v1/books', $this->validInput(['isbn' => '123456789012']))
            ->assertStatus(422)
            ->assertJsonPath('errors.isbn', ['ISBNは13桁の数字で入力してください。']);
        $this->postJson('/api/v1/books', $this->validInput(['isbn' => '9784873115641', 'title' => str_repeat('あ', 256)]))
            ->assertStatus(422)
            ->assertJsonPath('errors.title', ['タイトルは255文字以内で入力してください。']);
        // 配列の要素のエラーは、`genres.0` のように、何番目かが付いた名前で入る
        $this->postJson('/api/v1/books', $this->validInput(['isbn' => '9784873115641', 'genres' => [9999]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['genres.0' => '選択したジャンルは存在しません。']);
        $this->postJson('/api/v1/books', $this->validInput(['isbn' => '9784873115641', 'published_date' => '2026-02-30']))
            ->assertStatus(422)
            ->assertJsonPath('errors.published_date', ['出版日は正しい日付で入力してください。']);
        $this->postJson('/api/v1/books', $this->validInput(['isbn' => '9784873115641', 'description' => str_repeat('あ', 16001)]))
            ->assertStatus(422)
            ->assertJsonPath('errors.description', ['説明は16000文字以内で入力してください。']);
        $this->postJson('/api/v1/books', $this->validInput(['isbn' => '9784873115641', 'image_url' => 'not a url']))
            ->assertStatus(422)
            ->assertJsonPath('errors.image_url', ['画像URLはURLの形式で入力してください。']);
        $this->assertSame(1, Book::count());
    }

    // ---- 書籍更新（F-24）----

    public function test_update_changes_the_book_replaces_genres_and_returns_200_in_the_detail_shape(): void
    {
        $other = Genre::factory()->create(['name' => 'ビジネス']);
        $third = Genre::factory()->create(['name' => '技術書']);
        $book = $this->createBook(['isbn' => '9784873115658']);
        Review::factory()->create(['book_id' => $book->id, 'rating' => 5]);

        $response = $this->putJson("/api/v1/books/{$book->id}", $this->validInput([
            'title' => '更新後のタイトル',
            'genres' => [$other->id, $third->id],
        ]));

        $response->assertOk()
            ->assertJsonPath('data.title', '更新後のタイトル')
            ->assertJsonPath('data.reviews_count', 1)
            ->assertJsonPath('data.reviews_avg_rating', 5)
            ->assertJsonCount(1, 'data.reviews');
        $this->assertEqualsCanonicalizing([$other->id, $third->id], collect($response->json('data.genres'))->pluck('id')->all());
        $this->assertSame('更新後のタイトル', $book->fresh()->title);
        $this->assertEqualsCanonicalizing([$other->id, $third->id], $book->genres()->pluck('genres.id')->all());
    }

    public function test_update_changes_the_owner_only_when_user_id_is_sent(): void
    {
        $newOwner = User::factory()->create(['name' => '鈴木花子']);
        $book = $this->createBook(['isbn' => '9784873115658']);
        $withoutUserId = $this->validInput();
        unset($withoutUserId['user_id']);

        $this->putJson("/api/v1/books/{$book->id}", $withoutUserId)
            ->assertOk()
            ->assertJsonPath('data.user.id', $this->user->id);
        $this->putJson("/api/v1/books/{$book->id}", $this->validInput(['user_id' => $newOwner->id]))
            ->assertOk()
            ->assertJsonPath('data.user', ['id' => $newOwner->id, 'name' => '鈴木花子']);
        $this->putJson("/api/v1/books/{$book->id}", $this->validInput(['user_id' => 9999]))
            ->assertStatus(422)
            ->assertJsonPath('errors.user_id', ['登録者が存在しません。']);
        $this->assertSame($newOwner->id, $book->fresh()->user_id);
    }

    public function test_update_requires_all_book_fields_for_put_and_patch(): void
    {
        $book = $this->createBook(['isbn' => '9784873115658']);

        $this->putJson("/api/v1/books/{$book->id}", ['title' => '題名だけ'])
            ->assertStatus(422)
            ->assertJsonPath('errors.author', ['著者は必須です。'])
            ->assertJsonPath('errors.genres', ['ジャンルを1つ以上選択してください。']);
        $this->patchJson("/api/v1/books/{$book->id}", ['title' => '題名だけ'])
            ->assertStatus(422)
            ->assertJsonPath('errors.isbn', ['ISBNは必須です。']);
        $this->assertNotSame('題名だけ', $book->fresh()->title);
    }

    public function test_update_allows_own_isbn_but_rejects_isbn_of_another_book(): void
    {
        $book = $this->createBook(['isbn' => '9784873115658']);
        $this->createBook(['isbn' => '9784101010014']);

        $this->putJson("/api/v1/books/{$book->id}", $this->validInput(['isbn' => '9784873115658']))->assertOk();
        $this->putJson("/api/v1/books/{$book->id}", $this->validInput(['isbn' => '9784101010014']))
            ->assertStatus(422)
            ->assertJsonPath('errors.isbn', ['このISBNはすでに登録されています。']);
    }

    public function test_update_needs_neither_login_nor_csrf_token(): void
    {
        $book = $this->createBook(['isbn' => '9784873115658']);

        $this->assertGuest();
        $this->put("/api/v1/books/{$book->id}", $this->validInput(), ['Accept' => 'application/json'])->assertOk();
    }

    public function test_update_returns_404_for_missing_book_before_checking_input(): void
    {
        $this->putJson('/api/v1/books/9999', $this->validInput())
            ->assertNotFound()
            ->assertExactJson(['error' => '書籍が見つかりません。']);
        $this->putJson('/api/v1/books/9999', [])->assertNotFound();
    }

    // ---- 書籍削除（F-25）----

    public function test_destroy_returns_204_without_body_and_deletes_related_rows(): void
    {
        $book = $this->createBook();
        $review = Review::factory()->create(['book_id' => $book->id]);
        $review->likedByUsers()->attach($this->user->id);
        $this->user->favoriteBooks()->attach($book->id);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();
        $this->assertSame('', $response->getContent());
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('review_likes', ['review_id' => $review->id]);
        $this->assertDatabaseMissing('favorites', ['book_id' => $book->id]);
        $this->getJson("/api/v1/books/{$book->id}")->assertNotFound();
    }

    public function test_destroy_needs_neither_login_nor_csrf_token(): void
    {
        $book = $this->createBook();

        $this->assertGuest();
        $this->delete("/api/v1/books/{$book->id}", [], ['Accept' => 'application/json'])->assertNoContent();
    }

    public function test_destroy_returns_404_for_missing_book(): void
    {
        $this->deleteJson('/api/v1/books/9999')
            ->assertNotFound()
            ->assertExactJson(['error' => '書籍が見つかりません。']);
    }
}
