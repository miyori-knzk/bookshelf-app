<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 段階 2：ジャンルの一覧・詳細・登録・編集・削除（F-06〜F-10）。
 */
class GenreControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * ジャンルに書籍を n 冊紐付ける。
     */
    private function attachBooks(Genre $genre, int $count): void
    {
        Book::factory()->count($count)->create()->each(fn (Book $book) => $book->genres()->attach($genre));
    }

    // ---- 一覧（TP-9-x） ----

    public function test_index_lists_all_genres_by_name_without_paging(): void
    {
        collect(range(1, 12))->each(fn (int $i) => Genre::create(['name' => sprintf('ジャンル%02d', $i)]));

        $response = $this->actingAs($this->user)->get(route('genres.index'));

        $response->assertOk()
            ->assertSeeInOrder(['ジャンル01', 'ジャンル02', 'ジャンル11', 'ジャンル12'])
            ->assertViewHas('genres', fn ($genres) => $genres->count() === 12);
        $this->assertSame(
            collect(range(1, 12))->map(fn (int $i) => sprintf('ジャンル%02d', $i))->all(),
            $response->viewData('genres')->pluck('name')->all(),
        );
    }

    public function test_index_shows_book_count_per_genre(): void
    {
        $novel = Genre::create(['name' => '小説']);
        $cooking = Genre::create(['name' => '料理']);
        $this->attachBooks($novel, 3);

        $genres = $this->actingAs($this->user)->get(route('genres.index'))->viewData('genres');

        $this->assertSame(3, $genres->firstWhere('id', $novel->id)->books_count);
        $this->assertSame(0, $genres->firstWhere('id', $cooking->id)->books_count);
    }

    public function test_index_shows_message_when_no_genres(): void
    {
        $this->actingAs($this->user)->get(route('genres.index'))
            ->assertOk()
            ->assertSee('ジャンルが登録されていません。');
    }

    public function test_index_shows_edit_and_delete_to_users_who_did_not_create_the_genre(): void
    {
        $genre = Genre::create(['name' => '小説']);

        $this->actingAs(User::factory()->create())->get(route('genres.index'))
            ->assertOk()
            ->assertSee(route('genres.edit', $genre))
            ->assertSee(route('genres.destroy', $genre));
    }

    public function test_guest_is_redirected_to_login_from_index(): void
    {
        $this->get(route('genres.index'))->assertRedirect(route('login'));
    }

    // ---- 削除（TP-9-3・4・6・8） ----

    public function test_destroy_deletes_genre_without_books(): void
    {
        $genre = Genre::create(['name' => '料理']);

        $this->actingAs($this->user)->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('success', 'ジャンルを削除しました。');

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }

    public function test_destroy_is_refused_when_books_are_attached(): void
    {
        $genre = Genre::create(['name' => '小説']);
        $this->attachBooks($genre, 1);

        $this->actingAs($this->user)->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('error', '書籍が登録されているジャンルは削除できません。')
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }

    /**
     * 削除の後、リダイレクト先のジャンル一覧に、メッセージが画面で出ることを確かめる（TP-9-3・TP-9-4）。
     *
     * 一覧に出すこと自体は Q-46・Q-17。文言は Q-54 の想定（自分で決めたもの）なので、回答が変わったら直す。
     */
    public function test_index_shows_success_and_error_messages(): void
    {
        $withBooks = Genre::create(['name' => '小説']);
        $withoutBooks = Genre::create(['name' => '料理']);
        $this->attachBooks($withBooks, 1);

        // 書籍が紐付いている：エラーだけが出て、成功は出ない
        $this->actingAs($this->user)->followingRedirects()
            ->delete(route('genres.destroy', $withBooks))
            ->assertOk()
            ->assertSee('書籍が登録されているジャンルは削除できません。')
            ->assertDontSee('ジャンルを削除しました。')
            ->assertSee('小説');

        // 書籍が紐付いていない：成功だけが出て、エラーは出ない。削除したジャンルは一覧から消える
        $this->followingRedirects()
            ->delete(route('genres.destroy', $withoutBooks))
            ->assertOk()
            ->assertSee('ジャンルを削除しました。')
            ->assertDontSee('書籍が登録されているジャンルは削除できません。')
            ->assertDontSee('料理');
    }

    public function test_destroy_by_guest_does_not_delete(): void
    {
        $genre = Genre::create(['name' => '料理']);

        $this->delete(route('genres.destroy', $genre))->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }

    public function test_destroy_unknown_genre_is_404(): void
    {
        $this->actingAs($this->user)->delete(route('genres.destroy', 9999))->assertNotFound();
    }

    // ---- 詳細（TP-10-x） ----

    public function test_show_lists_only_books_of_the_genre_newest_first(): void
    {
        $genre = Genre::create(['name' => '小説']);
        $other = Genre::create(['name' => '料理']);
        $now = now();
        $old = Book::factory()->create(['title' => '古い本', 'created_at' => $now->copy()->subDay()]);
        $sameA = Book::factory()->create(['title' => '同時刻A', 'created_at' => $now]);
        $sameB = Book::factory()->create(['title' => '同時刻B', 'created_at' => $now]);
        $outside = Book::factory()->create(['title' => '別ジャンルの本']);
        collect([$old, $sameA, $sameB])->each(fn (Book $book) => $book->genres()->attach($genre));
        $outside->genres()->attach($other);

        $response = $this->actingAs($this->user)->get(route('genres.show', $genre));

        $response->assertOk()
            ->assertSee('ジャンル: 小説')
            ->assertDontSee('別ジャンルの本')
            ->assertSeeInOrder(['同時刻B', '同時刻A', '古い本']);
    }

    public function test_show_displays_other_genres_of_each_book(): void
    {
        $genre = Genre::create(['name' => '小説']);
        $other = Genre::create(['name' => '歴史']);
        $book = Book::factory()->create();
        $book->genres()->attach([$genre->id, $other->id]);

        $this->actingAs($this->user)->get(route('genres.show', $genre))
            ->assertOk()
            ->assertSee('歴史');
    }

    public function test_show_paginates_ten_per_page(): void
    {
        $genre = Genre::create(['name' => '小説']);
        $this->attachBooks($genre, 11);

        $this->actingAs($this->user)->get(route('genres.show', $genre))
            ->assertViewHas('books', fn ($books) => $books->count() === 10 && $books->lastPage() === 2);
        $this->get(route('genres.show', ['genre' => $genre, 'page' => 2]))
            ->assertViewHas('books', fn ($books) => $books->count() === 1);
    }

    public function test_show_fits_ten_books_on_one_page(): void
    {
        $genre = Genre::create(['name' => '小説']);
        $this->attachBooks($genre, 10);

        $this->actingAs($this->user)->get(route('genres.show', $genre))
            ->assertViewHas('books', fn ($books) => $books->count() === 10 && $books->lastPage() === 1);
    }

    public function test_show_displays_message_when_no_books(): void
    {
        $genre = Genre::create(['name' => '料理']);

        $this->actingAs($this->user)->get(route('genres.show', $genre))
            ->assertOk()
            ->assertSee('このジャンルの書籍はまだ登録されていません。');
    }

    public function test_show_links_back_to_book_index(): void
    {
        $genre = Genre::create(['name' => '小説']);

        $this->actingAs($this->user)->get(route('genres.show', $genre))
            ->assertSee('href="' . route('books.index') . '"', false);
    }

    public function test_show_by_guest_redirects_to_login(): void
    {
        $genre = Genre::create(['name' => '小説']);

        $this->get(route('genres.show', $genre))->assertRedirect(route('login'));
    }

    public function test_show_unknown_genre_is_404(): void
    {
        $this->actingAs($this->user)->get(route('genres.show', 9999))->assertNotFound();
    }

    // ---- 登録（TP-11-x） ----

    public function test_create_shows_form(): void
    {
        $this->actingAs($this->user)->get(route('genres.create'))
            ->assertOk()
            ->assertSee('ジャンル名');
    }

    public function test_store_creates_genre_and_redirects_to_index(): void
    {
        $this->actingAs($this->user)->post(route('genres.store'), ['name' => 'ミステリー'])
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('success', 'ジャンルを登録しました。');

        $this->assertDatabaseHas('genres', ['name' => 'ミステリー']);
    }

    public function test_store_requires_name(): void
    {
        $this->actingAs($this->user)->from(route('genres.create'))
            ->post(route('genres.store'), ['name' => ''])
            ->assertRedirect(route('genres.create'))
            ->assertSessionHasErrors(['name' => 'ジャンル名は必須です。']);

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_store_shows_required_error_below_name_field(): void
    {
        $this->actingAs($this->user)->from(route('genres.create'))
            ->followingRedirects()
            ->post(route('genres.store'), ['name' => ''])
            ->assertSee('ジャンル名は必須です。');
    }

    public function test_store_name_length_boundary(): void
    {
        $this->actingAs($this->user)->post(route('genres.store'), ['name' => str_repeat('あ', 255)])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('genres', 1);

        $this->from(route('genres.create'))->post(route('genres.store'), ['name' => str_repeat('い', 256)])
            ->assertSessionHasErrors(['name' => 'ジャンル名は255文字以内で入力してください。']);
        $this->assertDatabaseCount('genres', 1);
    }

    public function test_store_rejects_duplicate_name(): void
    {
        Genre::create(['name' => '小説']);

        $this->actingAs($this->user)->from(route('genres.create'))
            ->post(route('genres.store'), ['name' => '小説'])
            ->assertRedirect(route('genres.create'))
            ->assertSessionHasErrors(['name' => 'このジャンル名はすでに登録されています。']);

        $this->assertDatabaseCount('genres', 1);
    }

    /**
     * @dataProvider sameNameByCollationProvider
     */
    public function test_store_treats_names_differing_only_by_collation_as_duplicates(string $existing, string $input): void
    {
        Genre::create(['name' => $existing]);

        $this->actingAs($this->user)->post(route('genres.store'), ['name' => $input])
            ->assertSessionHasErrors(['name' => 'このジャンル名はすでに登録されています。']);

        $this->assertDatabaseCount('genres', 1);
    }

    /**
     * 照合順序 utf8mb4_unicode_ci で同じとみなされる名前（Q-48）。
     *
     * @return array<string, array{string, string}>
     */
    public static function sameNameByCollationProvider(): array
    {
        return [
            '大文字と小文字' => ['SF', 'sf'],
            '全角と半角' => ['SF', 'ＳＦ'],
            'ひらがなとカタカナ' => ['みすてりー', 'ミステリー'],
            '濁点・半濁点の有無' => ['ハ', 'バ'],
        ];
    }

    public function test_store_by_guest_does_not_save(): void
    {
        $this->post(route('genres.store'), ['name' => 'ミステリー'])->assertRedirect(route('login'));

        $this->assertDatabaseCount('genres', 0);
    }

    // ---- 編集（TP-12-x） ----

    public function test_edit_shows_current_name(): void
    {
        $genre = Genre::create(['name' => '旅行']);

        $this->actingAs($this->user)->get(route('genres.edit', $genre))
            ->assertOk()
            ->assertSee('value="旅行"', false);
    }

    public function test_update_changes_name_and_shows_new_name_on_show(): void
    {
        $genre = Genre::create(['name' => '旅行']);
        $this->attachBooks($genre, 1);

        $this->actingAs($this->user)->put(route('genres.update', $genre), ['name' => '紀行'])
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('success', 'ジャンルを更新しました。');

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '紀行']);
        $this->get(route('genres.show', $genre))->assertSee('ジャンル: 紀行');
    }

    public function test_update_allows_unchanged_name(): void
    {
        $genre = Genre::create(['name' => '紀行']);

        // 名前が同じでも、変えたときと同じ成功メッセージを出す（Q-65 は回答待ちの想定）
        $this->actingAs($this->user)->put(route('genres.update', $genre), ['name' => '紀行'])
            ->assertRedirect(route('genres.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'ジャンルを更新しました。');
    }

    public function test_update_rejects_name_of_another_genre(): void
    {
        $genre = Genre::create(['name' => '紀行']);
        Genre::create(['name' => '小説']);

        $this->actingAs($this->user)->from(route('genres.edit', $genre))
            ->put(route('genres.update', $genre), ['name' => '小説'])
            ->assertRedirect(route('genres.edit', $genre))
            ->assertSessionHasErrors(['name' => 'このジャンル名はすでに登録されています。']);

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '紀行']);
    }

    public function test_update_requires_name(): void
    {
        $genre = Genre::create(['name' => '紀行']);

        $this->actingAs($this->user)->from(route('genres.edit', $genre))
            ->put(route('genres.update', $genre), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'ジャンル名は必須です。']);
    }

    public function test_update_name_length_boundary(): void
    {
        $genre = Genre::create(['name' => '紀行']);

        $this->actingAs($this->user)->put(route('genres.update', $genre), ['name' => str_repeat('あ', 255)])
            ->assertSessionHasNoErrors();

        $this->from(route('genres.edit', $genre))
            ->put(route('genres.update', $genre), ['name' => str_repeat('い', 256)])
            ->assertRedirect(route('genres.edit', $genre))
            ->assertSessionHasErrors(['name' => 'ジャンル名は255文字以内で入力してください。']);
    }

    public function test_update_by_guest_does_not_save(): void
    {
        $genre = Genre::create(['name' => '紀行']);

        $this->put(route('genres.update', $genre), ['name' => '旅行'])->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '紀行']);
    }

    public function test_edit_and_update_unknown_genre_are_404(): void
    {
        $this->actingAs($this->user)->get(route('genres.edit', 9999))->assertNotFound();
        $this->put(route('genres.update', 9999), ['name' => '旅行'])->assertNotFound();
    }
}
