<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 段階 3：書籍の一覧・詳細・登録・編集・削除（F-01〜F-05）。
 */
class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Genre $genre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->genre = Genre::factory()->create(['name' => '小説']);
    }

    /**
     * 書籍の入力値。登録・編集のどちらでも通る内容に、上書きしたい項目だけ渡す。
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validInput(array $overrides = []): array
    {
        return [
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫の目から見た人間の話。',
            'image_url' => 'https://example.com/neko.jpg',
            'genres' => [$this->genre->id],
            ...$overrides,
        ];
    }

    /**
     * ジャンルを 1 つ付けた書籍を作る。
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createBook(array $attributes = [], ?User $owner = null): Book
    {
        $book = Book::factory()->create([...$attributes, 'user_id' => ($owner ?? $this->user)->id]);
        $book->genres()->attach($this->genre);

        return $book;
    }

    /**
     * 処理の間に実行された SQL の数を数える。
     */
    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    // ---- 一覧（TP-3-x） ----

    public function test_guest_can_open_book_index(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_index_shows_empty_message_when_no_books(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('書籍が登録されていません。');
    }

    public function test_index_orders_by_created_at_desc_then_id_desc(): void
    {
        $same = now()->subDay();
        $first = $this->createBook(['created_at' => $same]);
        $second = $this->createBook(['created_at' => $same]);
        $newest = $this->createBook(['created_at' => now()]);
        $oldest = $this->createBook(['created_at' => now()->subWeek()]);

        $ids = $this->get('/')->viewData('books')->pluck('id')->all();

        $this->assertSame([$newest->id, $second->id, $first->id, $oldest->id], $ids);
    }

    public function test_index_shows_average_rating_only_for_books_with_reviews(): void
    {
        $reviewed = $this->createBook(['title' => 'レビューあり']);
        $this->createBook(['title' => 'レビューなし']);
        Review::factory()->create(['book_id' => $reviewed->id, 'rating' => 4]);
        Review::factory()->create(['book_id' => $reviewed->id, 'rating' => 5]);

        $books = $this->get('/')->assertSee('(4.5)')->viewData('books');

        $this->assertEqualsWithDelta(4.5, $books->firstWhere('id', $reviewed->id)->reviews_avg_rating, 0.001);
        $this->assertNull($books->firstWhere('title', 'レビューなし')->reviews_avg_rating);
    }

    public function test_index_fits_ten_books_on_one_page_and_splits_eleven_into_two(): void
    {
        collect(range(1, 10))->each(fn () => $this->createBook());

        $books = $this->get('/')->viewData('books');
        $this->assertSame(1, $books->lastPage());
        $this->assertCount(10, $books);

        $this->createBook();

        $page1 = $this->get('/')->viewData('books');
        $page2 = $this->get('/?page=2')->viewData('books');
        $this->assertSame(2, $page1->lastPage());
        $this->assertCount(10, $page1);
        $this->assertCount(1, $page2);
    }

    public function test_index_query_count_does_not_grow_with_book_count(): void
    {
        $seed = function (int $count): void {
            collect(range(1, $count))->each(function () {
                $book = $this->createBook();
                $book->genres()->attach(Genre::factory()->create());
                Review::factory()->count(2)->create(['book_id' => $book->id]);
            });
        };

        $seed(2);
        $few = $this->countQueries(fn () => $this->get('/')->assertOk());

        $seed(6);
        $many = $this->countQueries(fn () => $this->get('/')->assertOk());

        $this->assertSame($few, $many);
    }

    // ---- 詳細（TP-4-x） ----

    public function test_guest_sees_book_detail_with_login_prompt_and_no_owner_actions(): void
    {
        $book = $this->createBook(['title' => '坊っちゃん', 'author' => '夏目漱石']);
        $review = Review::factory()->create(['book_id' => $book->id, 'comment' => '面白かった']);
        $review->likedByUsers()->attach($this->user);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('坊っちゃん')
            ->assertSee($book->isbn)
            ->assertSee('小説')
            ->assertSee('面白かった')
            ->assertSee('レビューを投稿するには')
            ->assertSee('ログイン</a>してください。', false)
            ->assertDontSee(route('books.edit', $book))
            ->assertDontSee('value="DELETE"', false);
    }

    public function test_owner_sees_edit_and_delete_but_other_user_does_not(): void
    {
        $book = $this->createBook();
        $other = User::factory()->create();

        $this->actingAs($this->user)->get(route('books.show', $book))
            ->assertOk()
            ->assertSee(route('books.edit', $book))
            ->assertSee('value="DELETE"', false);

        $this->actingAs($other)->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee(route('books.edit', $book))
            ->assertDontSee('value="DELETE"', false)
            ->assertDontSee('レビューを投稿するには');
    }

    public function test_show_hides_description_and_shows_placeholder_when_missing(): void
    {
        $book = $this->createBook(['description' => null, 'image_url' => null]);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('画像なし')
            ->assertDontSee('説明:');
    }

    public function test_show_lists_all_reviews_newest_first(): void
    {
        $book = $this->createBook();
        $same = now()->subDay();
        $first = Review::factory()->create(['book_id' => $book->id, 'created_at' => $same]);
        $second = Review::factory()->create(['book_id' => $book->id, 'created_at' => $same]);
        $newest = Review::factory()->create(['book_id' => $book->id, 'created_at' => now()]);
        Review::factory()->count(12)->create(['book_id' => $book->id, 'created_at' => now()->subWeek()]);

        $reviews = $this->get(route('books.show', $book))->viewData('book')->reviews;

        $this->assertCount(15, $reviews);
        $this->assertSame([$newest->id, $second->id, $first->id], $reviews->take(3)->pluck('id')->all());
    }

    public function test_show_says_no_reviews_yet_when_there_are_none(): void
    {
        $book = $this->createBook();

        $this->get(route('books.show', $book))->assertSee('まだレビューはありません。');
    }

    public function test_show_returns_404_for_missing_book(): void
    {
        $this->get('/books/9999')->assertNotFound();
    }

    public function test_show_query_count_does_not_grow_with_review_count(): void
    {
        $book = $this->createBook();
        $addReviews = function (int $count) use ($book): void {
            Review::factory()->count($count)->create(['book_id' => $book->id])
                ->each(fn (Review $review) => $review->likedByUsers()->attach(User::factory()->create()));
        };

        $addReviews(1);
        // 同じ User を使い回すと、お気に入り・いいね済みの関係が 2 回目で読み込み済みになるため、毎回取り直す
        $few = $this->countQueries(fn () => $this->actingAs($this->user->fresh())->get(route('books.show', $book))->assertOk());

        $addReviews(6);
        $many = $this->countQueries(fn () => $this->actingAs($this->user->fresh())->get(route('books.show', $book))->assertOk());

        $this->assertSame($few, $many);
    }

    // ---- 登録（TP-5-x） ----

    public function test_guest_cannot_open_or_submit_create_form(): void
    {
        $this->get(route('books.create'))->assertRedirect(route('login'));
        $this->post(route('books.store'), $this->validInput())->assertRedirect(route('login'));

        $this->assertDatabaseCount('books', 0);
    }

    public function test_create_form_lists_genres_by_name(): void
    {
        Genre::factory()->create(['name' => 'ビジネス']);
        Genre::factory()->create(['name' => '旅行']);

        $genres = $this->actingAs($this->user)->get(route('books.create'))
            ->assertOk()
            ->viewData('genres');

        $this->assertSame(Genre::orderBy('name')->pluck('name')->all(), $genres->pluck('name')->all());
    }

    public function test_create_form_says_register_genre_first_when_no_genres(): void
    {
        Genre::query()->delete();

        $this->actingAs($this->user)->get(route('books.create'))
            ->assertOk()
            ->assertSee('ジャンルが登録されていません。先にジャンルを登録してください。');
    }

    public function test_store_creates_book_with_genres_and_redirects_to_show(): void
    {
        $second = Genre::factory()->create(['name' => 'エッセイ']);

        $response = $this->actingAs($this->user)
            ->post(route('books.store'), $this->validInput(['genres' => [$this->genre->id, $second->id]]));

        $book = Book::firstOrFail();
        $response->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', '書籍を登録しました。');
        $this->assertSame($this->user->id, $book->user_id);
        $this->assertSame('吾輩は猫である', $book->title);
        $this->assertEqualsCanonicalizing([$this->genre->id, $second->id], $book->genres()->pluck('genres.id')->all());
    }

    public function test_store_saves_empty_description_and_image_url_as_null(): void
    {
        $this->actingAs($this->user)
            ->post(route('books.store'), $this->validInput(['description' => '', 'image_url' => '']))
            ->assertSessionHasNoErrors();

        $book = Book::firstOrFail();
        $this->assertNull($book->description);
        $this->assertNull($book->image_url);
    }

    public function test_store_shows_message_per_missing_required_field_and_keeps_input(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('books.create'))
            ->post(route('books.store'), ['author' => '夏目漱石']);

        $response->assertRedirect(route('books.create'))
            ->assertSessionHasErrors([
                'title' => 'タイトルは必須です。',
                'isbn' => 'ISBNは必須です。',
                'published_date' => '出版日は必須です。',
                'genres' => 'ジャンルを1つ以上選択してください。',
            ])
            ->assertSessionHasInput('author', '夏目漱石');
        $this->assertDatabaseCount('books', 0);
    }

    public function test_store_keeps_genre_selection_when_validation_fails(): void
    {
        $this->actingAs($this->user)
            ->from(route('books.create'))
            ->post(route('books.store'), $this->validInput(['title' => '']));

        $html = $this->actingAs($this->user)->get(route('books.create'))->getContent();

        $this->assertSame(1, preg_match('/value="' . $this->genre->id . '"[^>]*checked/s', $html));
    }

    /**
     * @dataProvider lengthBoundaryProvider
     */
    public function test_store_enforces_max_length(string $field, int $max, string $message): void
    {
        $value = fn (int $length): string => $field === 'image_url'
            ? 'https://example.com/' . str_repeat('a', $length - 20)
            : str_repeat('あ', $length);

        $this->actingAs($this->user)->post(route('books.store'), $this->validInput([$field => $value($max)]))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->user)->post(route('books.store'), $this->validInput([
            'isbn' => '9784101010021',
            $field => $value($max + 1),
        ]))->assertSessionHasErrors([$field => $message]);
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function lengthBoundaryProvider(): array
    {
        return [
            'タイトル' => ['title', 255, 'タイトルは255文字以内で入力してください。'],
            '著者' => ['author', 255, '著者は255文字以内で入力してください。'],
            '説明' => ['description', 16000, '説明は16000文字以内で入力してください。'],
            '画像URL' => ['image_url', 255, '画像URLは255文字以内で入力してください。'],
        ];
    }

    /**
     * @dataProvider invalidIsbnProvider
     */
    public function test_store_rejects_invalid_isbn(string $isbn): void
    {
        $this->actingAs($this->user)->post(route('books.store'), $this->validInput(['isbn' => $isbn]))
            ->assertSessionHasErrors(['isbn' => 'ISBNは13桁の数字で入力してください。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidIsbnProvider(): array
    {
        return [
            '12桁' => ['978410101001'],
            '14桁' => ['97841010100140'],
            '数字以外' => ['978410101001X'],
            'ハイフン入り' => ['978-4101010014'],
        ];
    }

    public function test_store_rejects_duplicate_isbn(): void
    {
        $this->createBook(['isbn' => '9784101010014']);

        $this->actingAs($this->user)->post(route('books.store'), $this->validInput())
            ->assertSessionHasErrors(['isbn' => 'このISBNはすでに登録されています。']);

        $this->assertDatabaseCount('books', 1);
    }

    /**
     * @dataProvider invalidDateProvider
     */
    public function test_store_rejects_invalid_published_date(string $date): void
    {
        $this->actingAs($this->user)->post(route('books.store'), $this->validInput(['published_date' => $date]))
            ->assertSessionHasErrors(['published_date' => '出版日は正しい日付で入力してください。']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidDateProvider(): array
    {
        return [
            '2月30日' => ['2024-02-30'],
            '英語の書式' => ['January 2 2024'],
            'スラッシュ区切り' => ['2024/01/02'],
        ];
    }

    public function test_store_accepts_future_published_date(): void
    {
        $this->actingAs($this->user)
            ->post(route('books.store'), $this->validInput(['published_date' => now()->addYear()->format('Y-m-d')]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('books', 1);
    }

    public function test_store_rejects_invalid_image_url(): void
    {
        $this->actingAs($this->user)->post(route('books.store'), $this->validInput(['image_url' => 'not a url']))
            ->assertSessionHasErrors(['image_url' => '画像URLはURLの形式で入力してください。']);
    }

    public function test_store_rejects_unknown_or_malformed_genres(): void
    {
        $this->actingAs($this->user)->post(route('books.store'), $this->validInput(['genres' => [9999]]))
            ->assertSessionHasErrors(['genres.0' => '選択したジャンルは存在しません。']);

        $this->actingAs($this->user)->post(route('books.store'), $this->validInput(['genres' => ['abc']]))
            ->assertSessionHasErrors(['genres.0' => '選択したジャンルが正しくありません。']);

        $this->actingAs($this->user)->post(route('books.store'), $this->validInput(['genres' => 'abc']))
            ->assertSessionHasErrors(['genres' => 'ジャンルの選択内容が正しくありません。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 画面の入力欄は必ず文字列で送るため、画面からは出せない。送信内容を書き換えたときのメッセージを確かめる。
     *
     * @dataProvider nonStringFieldProvider
     */
    public function test_store_rejects_non_string_text_fields(string $field, string $message): void
    {
        $this->actingAs($this->user)->post(route('books.store'), $this->validInput([$field => ['配列']]))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function nonStringFieldProvider(): array
    {
        return [
            'タイトル' => ['title', 'タイトルは文字列で入力してください。'],
            '著者' => ['author', '著者は文字列で入力してください。'],
            '説明' => ['description', '説明は文字列で入力してください。'],
        ];
    }

    public function test_create_with_genres_leaves_no_book_when_linking_genres_fails(): void
    {
        try {
            Book::createWithGenres(
                Book::factory()->make(['user_id' => $this->user->id])->getAttributes(),
                [9999],
            );
            $this->fail('外部キーのエラーになるはずが、例外が出なかった。');
        } catch (QueryException) {
            $this->assertDatabaseCount('books', 0);
        }
    }

    // ---- 編集・更新（TP-6-x） ----

    public function test_guest_is_sent_to_login_for_edit_and_update(): void
    {
        $book = $this->createBook();

        $this->get(route('books.edit', $book))->assertRedirect(route('login'));
        $this->put(route('books.update', $book), $this->validInput())->assertRedirect(route('login'));
    }

    public function test_edit_form_has_current_values_and_selected_genres(): void
    {
        $other = Genre::factory()->create(['name' => 'エッセイ']);
        Genre::factory()->create(['name' => '旅行']);
        $book = $this->createBook(['title' => '坊っちゃん', 'published_date' => '1906-04-01']);
        $book->genres()->attach($other);

        $response = $this->actingAs($this->user)->get(route('books.edit', $book))
            ->assertOk()
            ->assertSee('value="坊っちゃん"', false)
            ->assertSee('value="1906-04-01"', false);

        $html = $response->getContent();
        $this->assertSame(1, preg_match('/value="' . $this->genre->id . '"[^>]*checked/s', $html));
        $this->assertSame(1, preg_match('/value="' . $other->id . '"[^>]*checked/s', $html));
        $this->assertSame(2, preg_match_all('/name="genres\[\]"[^>]*checked/s', $html));
        $this->assertSame(
            Genre::orderBy('name')->pluck('name')->all(),
            $response->viewData('genres')->pluck('name')->all(),
        );
    }

    public function test_update_changes_book_syncs_genres_and_redirects_to_show(): void
    {
        $removed = Genre::factory()->create(['name' => '旅行']);
        $added = Genre::factory()->create(['name' => 'ビジネス']);
        $book = $this->createBook();
        $book->genres()->attach($removed);

        $this->actingAs($this->user)
            ->put(route('books.update', $book), $this->validInput([
                'title' => '吾輩は猫である（改訂版）',
                'genres' => [$this->genre->id, $added->id],
            ]))
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', '書籍を更新しました。');

        $this->assertSame('吾輩は猫である（改訂版）', $book->fresh()->title);
        $this->assertEqualsCanonicalizing(
            [$this->genre->id, $added->id],
            $book->genres()->pluck('genres.id')->all(),
        );
    }

    public function test_update_allows_keeping_own_isbn(): void
    {
        $book = $this->createBook(['isbn' => '9784101010014']);

        $this->actingAs($this->user)
            ->put(route('books.update', $book), $this->validInput(['isbn' => '9784101010014', 'title' => '新しい題名']))
            ->assertSessionHasNoErrors();

        $this->assertSame('新しい題名', $book->fresh()->title);
    }

    public function test_update_rejects_isbn_of_another_book(): void
    {
        $this->createBook(['isbn' => '9784101010021']);
        $book = $this->createBook(['isbn' => '9784101010014']);

        $this->actingAs($this->user)
            ->put(route('books.update', $book), $this->validInput(['isbn' => '9784101010021']))
            ->assertSessionHasErrors(['isbn' => 'このISBNはすでに登録されています。']);

        $this->assertSame('9784101010014', $book->fresh()->isbn);
    }

    public function test_update_applies_same_validation_as_store(): void
    {
        $book = $this->createBook();

        $this->actingAs($this->user)
            ->put(route('books.update', $book), ['title' => ''])
            ->assertSessionHasErrors(['title', 'author', 'isbn', 'published_date', 'genres']);
    }

    public function test_non_owner_gets_403_for_edit_and_update_and_nothing_is_saved(): void
    {
        $book = $this->createBook(['title' => '元の題名']);
        $other = User::factory()->create();

        $this->actingAs($other)->get(route('books.edit', $book))->assertForbidden();
        $this->actingAs($other)->put(route('books.update', $book), $this->validInput(['isbn' => $book->isbn]))
            ->assertForbidden();

        $this->assertSame('元の題名', $book->fresh()->title);
    }

    public function test_non_owner_sending_invalid_input_gets_validation_error_not_403(): void
    {
        $book = $this->createBook(['title' => '元の題名']);

        $this->actingAs(User::factory()->create())
            ->put(route('books.update', $book), ['title' => ''])
            ->assertSessionHasErrors('title');

        $this->assertSame('元の題名', $book->fresh()->title);
    }

    public function test_edit_and_update_return_404_for_missing_book(): void
    {
        $this->actingAs($this->user)->get('/books/9999/edit')->assertNotFound();
        $this->actingAs($this->user)->put('/books/9999', $this->validInput())->assertNotFound();
    }

    public function test_update_with_genres_leaves_book_unchanged_when_linking_genres_fails(): void
    {
        $book = $this->createBook(['title' => '元の題名']);

        try {
            $book->updateWithGenres(['title' => '新しい題名'], [9999]);
            $this->fail('外部キーのエラーになるはずが、例外が出なかった。');
        } catch (QueryException) {
            $this->assertSame('元の題名', $book->fresh()->title);
            $this->assertSame([$this->genre->id], $book->genres()->pluck('genres.id')->all());
        }
    }

    // ---- 削除（TP-4-16・4-18） ----

    public function test_owner_can_delete_book_and_related_rows_are_deleted_too(): void
    {
        $book = $this->createBook();
        $keep = $this->createBook();
        $fan = User::factory()->create();
        $fan->favoriteBooks()->attach($book);
        $review = Review::factory()->create(['book_id' => $book->id]);
        $review->likedByUsers()->attach($fan);

        $this->actingAs($this->user)->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'))
            ->assertSessionHas('success', '書籍を削除しました。');

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('favorites', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('reviews', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('review_likes', ['review_id' => $review->id]);
        $this->assertDatabaseHas('books', ['id' => $keep->id]);
        $this->assertDatabaseHas('book_genre', ['book_id' => $keep->id]);
    }

    public function test_non_owner_cannot_delete_book(): void
    {
        $book = $this->createBook();

        $this->actingAs(User::factory()->create())->delete(route('books.destroy', $book))->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    public function test_guest_cannot_delete_book(): void
    {
        $book = $this->createBook();

        $this->delete(route('books.destroy', $book))->assertRedirect(route('login'));

        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    public function test_delete_returns_404_for_missing_book(): void
    {
        $this->actingAs($this->user)->delete('/books/9999')->assertNotFound();
    }
}
