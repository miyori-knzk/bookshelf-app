<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * 段階 5：お気に入りの切り替えと一覧（F-15・F-16）。
 */
class FavoriteControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * 押した画面（Referer）を付けて、お気に入りの切り替えを送る。
     */
    private function toggle(User $user, Book $book, string $referer): TestResponse
    {
        return $this->actingAs($user)
            ->withHeader('Referer', $referer)
            ->post(route('favorites.toggle', $book));
    }

    /**
     * 1 分ずつ時刻を進めながら、書籍をお気に入りに追加する。後に追加したものほど、追加日時が新しい。
     *
     * @param  iterable<Book>  $books
     */
    private function addFavoritesOneMinuteApart(User $user, iterable $books): void
    {
        $start = now()->startOfSecond();

        collect($books)->values()->each(function (Book $book, int $index) use ($user, $start): void {
            $this->travelTo($start->copy()->addMinutes($index));
            $user->favoriteBooks()->attach($book->id);
        });

        $this->travelBack();
    }

    /**
     * 指定した数の書籍を作り、タイトルを「書籍01」「書籍02」…にする（作った順。前方一致しないよう 2 桁にする）。
     *
     * @return Collection<int, Book>
     */
    private function createBooks(int $count): Collection
    {
        return collect(range(1, $count))
            ->map(fn (int $number): Book => Book::factory()->create(['title' => sprintf('書籍%02d', $number)]));
    }

    // --- 切り替え（F-15） ---

    public function test_toggle_adds_a_favorite_and_returns_to_book_detail(): void
    {
        $book = Book::factory()->create();
        $detail = route('books.show', $book);

        $this->toggle($this->user, $book, $detail)->assertRedirect($detail);

        $this->assertDatabaseHas('favorites', ['user_id' => $this->user->id, 'book_id' => $book->id]);
    }

    public function test_toggle_removes_the_favorite_when_pressed_again(): void
    {
        $book = Book::factory()->create();
        $detail = route('books.show', $book);

        $this->toggle($this->user, $book, $detail);
        $this->toggle($this->user, $book, $detail)->assertRedirect($detail);

        $this->assertDatabaseMissing('favorites', ['user_id' => $this->user->id, 'book_id' => $book->id]);
    }

    public function test_toggle_changes_the_heart_on_book_detail(): void
    {
        $book = Book::factory()->create();

        $this->actingAs($this->user)->get(route('books.show', $book))->assertSee('お気に入りに追加');

        $this->toggle($this->user, $book, route('books.show', $book));

        // 1 回目の表示で読み込んだ favoriteBooks が残らないよう、新しく読み直したユーザーで開く
        $this->actingAs(User::find($this->user->id))->get(route('books.show', $book))
            ->assertSee('お気に入りから削除')
            ->assertDontSee('お気に入りに追加');
    }

    public function test_toggle_from_the_favorites_list_returns_to_the_list_page(): void
    {
        $book = Book::factory()->create();
        $this->user->favoriteBooks()->attach($book->id);
        $listPage = route('favorites.index', ['page' => 2]);

        $this->toggle($this->user, $book, $listPage)->assertRedirect($listPage);

        $this->assertDatabaseMissing('favorites', ['book_id' => $book->id]);
    }

    public function test_toggle_returns_to_the_previous_page_in_the_session_when_there_is_no_referer(): void
    {
        $book = Book::factory()->create();
        $detail = route('books.show', $book);

        $this->actingAs($this->user)->from($detail)->post(route('favorites.toggle', $book))->assertRedirect($detail);
    }

    public function test_toggle_goes_to_top_page_when_there_is_no_referer_and_no_previous_page(): void
    {
        $book = Book::factory()->create();

        $this->actingAs($this->user)->post(route('favorites.toggle', $book))->assertRedirect('/');

        $this->assertDatabaseHas('favorites', ['user_id' => $this->user->id, 'book_id' => $book->id]);
    }

    public function test_toggle_only_changes_the_favorites_of_the_logged_in_user(): void
    {
        $book = Book::factory()->create();
        $other = User::factory()->create();
        $other->favoriteBooks()->attach($book->id);

        $this->toggle($this->user, $book, route('books.show', $book));

        $this->assertDatabaseHas('favorites', ['user_id' => $other->id, 'book_id' => $book->id]);
        $this->assertDatabaseHas('favorites', ['user_id' => $this->user->id, 'book_id' => $book->id]);
    }

    public function test_toggle_by_guest_is_sent_to_login_and_nothing_is_saved(): void
    {
        $book = Book::factory()->create();

        $this->post(route('favorites.toggle', $book))->assertRedirect(route('login'));

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_toggle_returns_404_for_missing_book(): void
    {
        $this->actingAs($this->user)->post('/books/9999/favorites')->assertNotFound();
    }

    // --- 一覧（F-16） ---

    public function test_index_shows_only_my_favorites(): void
    {
        [$mine, $theirs, $none] = $this->createBooks(3)->all();
        $other = User::factory()->create();
        $this->user->favoriteBooks()->attach($mine->id);
        $other->favoriteBooks()->attach($theirs->id);

        $this->actingAs($this->user)->get(route('favorites.index'))
            ->assertOk()
            ->assertSee($mine->title)
            ->assertDontSee($theirs->title)
            ->assertDontSee($none->title);
    }

    public function test_index_orders_by_added_time_newest_first_not_by_book_registration(): void
    {
        $books = $this->createBooks(3);
        // 書籍の登録順（昇順・降順）のどちらとも違う順に、お気に入りへ追加する
        $this->addFavoritesOneMinuteApart($this->user, [$books[2], $books[0], $books[1]]);

        $this->actingAs($this->user)->get(route('favorites.index'))
            ->assertSeeInOrder(['書籍02', '書籍01', '書籍03']);
    }

    public function test_index_orders_by_favorite_id_desc_when_added_at_the_same_time(): void
    {
        $books = $this->createBooks(3);
        $this->freezeTime();
        // 追加日時がすべて同じ。favorites.id は追加した順に大きくなる
        $this->user->favoriteBooks()->attach($books[1]->id);
        $this->user->favoriteBooks()->attach($books[2]->id);
        $this->user->favoriteBooks()->attach($books[0]->id);

        $this->actingAs($this->user)->get(route('favorites.index'))
            ->assertSeeInOrder(['書籍01', '書籍03', '書籍02']);
    }

    public function test_index_fits_ten_favorites_on_one_page_and_splits_eleven_into_two(): void
    {
        $books = $this->createBooks(11);
        $this->addFavoritesOneMinuteApart($this->user, $books->take(10));

        $this->actingAs($this->user)->get(route('favorites.index'))->assertOk()->assertDontSee('page=2');

        $this->addFavoritesOneMinuteApart($this->user, $books->slice(10));

        $first = $this->actingAs($this->user)->get(route('favorites.index'))->assertOk();
        $first->assertSee('書籍11')->assertDontSee('書籍01');

        // 2 ページ目は、いちばん古く追加した 1 冊だけ
        $this->actingAs($this->user)->get(route('favorites.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('書籍01')
            ->assertDontSee('書籍11');
    }

    public function test_index_shows_message_and_link_when_there_are_no_favorites(): void
    {
        $this->actingAs($this->user)->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('お気に入りに登録された書籍はありません。')
            ->assertSee('書籍一覧を見る');
    }

    public function test_index_by_guest_is_sent_to_login(): void
    {
        $this->get(route('favorites.index'))->assertRedirect(route('login'));
    }

    public function test_removing_the_only_book_on_the_last_page_moves_to_the_last_page_with_books(): void
    {
        $books = $this->createBooks(11);
        $this->addFavoritesOneMinuteApart($this->user, $books);
        $secondPage = route('favorites.index', ['page' => 2]);

        // 2 ページ目の 1 冊（いちばん古い「書籍01」）を、2 ページ目から解除する
        $this->toggle($this->user, $books[0], $secondPage)->assertRedirect($secondPage);

        $this->actingAs($this->user)->get($secondPage)
            ->assertRedirect(route('favorites.index', ['page' => 1]));
        $this->actingAs($this->user)->get(route('favorites.index', ['page' => 1]))
            ->assertOk()
            ->assertSee('書籍11')
            ->assertDontSee('お気に入りに登録された書籍はありません。');
    }

    public function test_removing_one_of_several_books_on_the_last_page_stays_on_that_page(): void
    {
        $books = $this->createBooks(12);
        $this->addFavoritesOneMinuteApart($this->user, $books);
        $secondPage = route('favorites.index', ['page' => 2]);

        // 1 ページ目は書籍12〜書籍03、2 ページ目は書籍02・書籍01。2 ページ目から書籍02 を解除する
        $this->toggle($this->user, $books[1], $secondPage)->assertRedirect($secondPage);

        $this->assertDatabaseMissing('favorites', ['user_id' => $this->user->id, 'book_id' => $books[1]->id]);

        // 2 ページ目は移動せずに開け、残りの 1 冊（書籍01）だけが出る
        $this->actingAs($this->user)->get($secondPage)
            ->assertOk()
            ->assertSee('書籍01')
            ->assertDontSee('書籍02');

        // 1 ページ目は、書籍12〜書籍03 のまま変わらない
        $this->actingAs($this->user)->get(route('favorites.index'))
            ->assertOk()
            ->assertSeeInOrder(['書籍12', '書籍11', '書籍10', '書籍09', '書籍08', '書籍07', '書籍06', '書籍05', '書籍04', '書籍03'])
            ->assertDontSee('書籍01');
    }

    public function test_page_beyond_the_last_page_moves_to_the_last_page_with_books(): void
    {
        $this->addFavoritesOneMinuteApart($this->user, $this->createBooks(11));

        $this->actingAs($this->user)->get(route('favorites.index', ['page' => 99]))
            ->assertRedirect(route('favorites.index', ['page' => 2]));
        $this->actingAs($this->user)->get(route('favorites.index', ['page' => 100]))
            ->assertRedirect(route('favorites.index', ['page' => 2]));
    }

    public function test_page_that_is_not_a_positive_integer_shows_the_first_page(): void
    {
        $this->addFavoritesOneMinuteApart($this->user, $this->createBooks(11));

        collect(['0', '-1', 'abc'])->each(fn (string $page) => $this->actingAs($this->user)
            ->get(route('favorites.index', ['page' => $page]))
            ->assertOk()
            ->assertSee('書籍11'));
    }

    public function test_page_beyond_the_first_with_no_favorites_moves_to_the_first_page(): void
    {
        $this->actingAs($this->user)->get(route('favorites.index', ['page' => 2]))
            ->assertRedirect(route('favorites.index', ['page' => 1]));
        $this->actingAs($this->user)->get(route('favorites.index', ['page' => 1]))
            ->assertOk()
            ->assertSee('お気に入りに登録された書籍はありません。');
    }

    public function test_list_displays_unregistered_for_a_book_without_isbn(): void
    {
        $book = Book::factory()->create(['isbn' => null]);
        $this->user->favoriteBooks()->attach($book->id);

        $this->actingAs($this->user)->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('ISBN: 未登録');
    }
}
