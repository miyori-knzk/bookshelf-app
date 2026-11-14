<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * 段階 4：レビューの投稿・編集・削除（F-11〜F-13）。
 */
class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->book = Book::factory()->create();
    }

    /**
     * レビューの入力値。投稿・編集のどちらでも通る内容に、上書きしたい項目だけ渡す。
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validInput(array $overrides = []): array
    {
        return ['rating' => 4, 'comment' => '面白かった', ...$overrides];
    }

    /**
     * 投稿画面（書籍詳細）から投稿を送る。入力エラーのときの戻り先は、直前の画面（書籍詳細）になる。
     *
     * @param  array<string, mixed>  $input
     */
    private function postReview(User $user, array $input): TestResponse
    {
        return $this->actingAs($user)
            ->from(route('books.show', $this->book))
            ->post(route('reviews.store', $this->book), $input);
    }

    /**
     * 編集画面から更新を送る。入力エラーのときの戻り先は、直前の画面（編集画面）になる。
     *
     * @param  array<string, mixed>  $input
     */
    private function putReview(User $user, Review $review, array $input): TestResponse
    {
        return $this->actingAs($user)
            ->from(route('reviews.edit', $review))
            ->put(route('reviews.update', $review), $input);
    }

    private function createReview(?User $owner = null, array $attributes = []): Review
    {
        return Review::factory()->create([
            'book_id' => $this->book->id,
            'user_id' => ($owner ?? $this->user)->id,
            'rating' => 5,
            'comment' => '面白かった',
            ...$attributes,
        ]);
    }

    // ---- 投稿（F-11、TP-4-6〜4-10・4-15） ----

    public function test_store_saves_review_and_redirects_to_book_detail(): void
    {
        $this->postReview($this->user, $this->validInput(['rating' => 5, 'comment' => '面白かった']))
            ->assertRedirect(route('books.show', $this->book))
            ->assertSessionHas('success', 'レビューを投稿しました。');

        $this->assertDatabaseHas('reviews', [
            'book_id' => $this->book->id,
            'user_id' => $this->user->id,
            'rating' => 5,
            'comment' => '面白かった',
        ]);
    }

    public function test_posted_review_appears_first_on_book_detail(): void
    {
        $this->createReview(attributes: ['comment' => '前のレビュー']);

        $this->postReview($this->user, $this->validInput(['comment' => '新しいレビュー']));

        $this->get(route('books.show', $this->book))->assertSeeInOrder(['新しいレビュー', '前のレビュー']);
    }

    public function test_same_user_can_post_two_reviews_for_the_same_book(): void
    {
        $this->postReview($this->user, $this->validInput(['comment' => '1 件目']));
        $this->postReview($this->user, $this->validInput(['comment' => '2 件目']));

        $this->assertSame(2, Review::where('book_id', $this->book->id)->where('user_id', $this->user->id)->count());
    }

    public function test_store_without_comment_returns_to_book_detail_with_message_and_keeps_rating(): void
    {
        $this->postReview($this->user, ['rating' => 3, 'comment' => ''])
            ->assertRedirect(route('books.show', $this->book))
            ->assertSessionHasErrors(['comment' => 'コメントを入力してください。']);

        $this->assertSame(0, Review::count());

        $this->followingRedirects()
            ->actingAs($this->user)
            ->from(route('books.show', $this->book))
            ->post(route('reviews.store', $this->book), ['rating' => 3, 'comment' => ''])
            ->assertSee('コメントを入力してください。')
            ->assertSee('<option value="3" selected>', false);
    }

    public function test_store_without_rating_returns_to_book_detail_with_message_and_keeps_comment(): void
    {
        $this->postReview($this->user, ['comment' => '面白かった'])
            ->assertRedirect(route('books.show', $this->book))
            ->assertSessionHasErrors(['rating' => '評価を選択してください。'])
            ->assertSessionHasInput('comment', '面白かった');

        $this->assertSame(0, Review::count());
    }

    /**
     * @dataProvider validRatingProvider
     */
    public function test_store_accepts_rating_from_1_to_5(int $rating): void
    {
        $this->postReview($this->user, $this->validInput(['rating' => $rating]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reviews', ['rating' => $rating]);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function validRatingProvider(): array
    {
        return ['下限 1' => [1], '上限 5' => [5]];
    }

    /**
     * @dataProvider invalidRatingProvider
     */
    public function test_store_rejects_rating_outside_1_to_5_or_not_a_number(string $rating): void
    {
        $this->postReview($this->user, $this->validInput(['rating' => $rating]))
            ->assertSessionHasErrors(['rating' => '評価は1〜5の中から選択してください。']);

        $this->assertSame(0, Review::count());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidRatingProvider(): array
    {
        return ['0' => ['0'], '6' => ['6'], '小数' => ['3.5'], '数字以外' => ['abc']];
    }

    public function test_store_accepts_comment_of_1000_characters_and_rejects_1001(): void
    {
        $this->postReview($this->user, $this->validInput(['comment' => str_repeat('あ', 1000)]))
            ->assertSessionHasNoErrors();

        $this->postReview($this->user, $this->validInput(['comment' => str_repeat('あ', 1001)]))
            ->assertSessionHasErrors(['comment' => 'コメントは1000文字以内で入力してください。']);

        $this->assertSame(1, Review::count());
    }

    public function test_guest_cannot_post_review(): void
    {
        $this->post(route('reviews.store', $this->book), $this->validInput())
            ->assertRedirect(route('login'));

        $this->assertSame(0, Review::count());
    }

    public function test_store_returns_404_for_missing_book(): void
    {
        $this->actingAs($this->user)->post('/books/9999/reviews', $this->validInput())->assertNotFound();
    }

    // ---- 編集画面・更新（F-12、TP-7-1〜7-7） ----

    public function test_edit_form_shows_book_title_and_current_values(): void
    {
        $review = $this->createReview(attributes: ['rating' => 5, 'comment' => '面白かった']);

        $this->actingAs($this->user)->get(route('reviews.edit', $review))
            ->assertOk()
            ->assertSee($this->book->title)
            ->assertSee('面白かった')
            ->assertSee('value="5" class="sr-only peer" checked', false);
    }

    public function test_update_changes_review_and_redirects_to_book_detail(): void
    {
        $review = $this->createReview();

        $this->putReview($this->user, $review, ['rating' => 3, 'comment' => '普通だった'])
            ->assertRedirect(route('books.show', $this->book))
            ->assertSessionHas('success', 'レビューを更新しました。');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'book_id' => $this->book->id,
            'user_id' => $this->user->id,
            'rating' => 3,
            'comment' => '普通だった',
        ]);
    }

    public function test_update_without_comment_returns_to_edit_form_with_message(): void
    {
        $review = $this->createReview();

        $this->putReview($this->user, $review, ['rating' => 3, 'comment' => ''])
            ->assertRedirect(route('reviews.edit', $review))
            ->assertSessionHasErrors(['comment' => 'コメントを入力してください。']);

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 5, 'comment' => '面白かった']);
    }

    /**
     * @dataProvider validRatingProvider
     */
    public function test_update_accepts_rating_from_1_to_5(int $rating): void
    {
        $review = $this->createReview();

        $this->putReview($this->user, $review, $this->validInput(['rating' => $rating]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => $rating]);
    }

    /**
     * @dataProvider invalidRatingProvider
     */
    public function test_update_rejects_rating_outside_1_to_5_or_not_a_number(string $rating): void
    {
        $review = $this->createReview();

        $this->putReview($this->user, $review, $this->validInput(['rating' => $rating]))
            ->assertSessionHasErrors(['rating' => '評価は1〜5の中から選択してください。']);

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 5]);
    }

    public function test_update_accepts_comment_of_1000_characters_and_rejects_1001(): void
    {
        $review = $this->createReview();

        $this->putReview($this->user, $review, $this->validInput(['comment' => str_repeat('あ', 1000)]))
            ->assertSessionHasNoErrors();

        $this->putReview($this->user, $review, $this->validInput(['comment' => str_repeat('い', 1001)]))
            ->assertRedirect(route('reviews.edit', $review))
            ->assertSessionHasErrors(['comment' => 'コメントは1000文字以内で入力してください。']);

        $this->assertSame(str_repeat('あ', 1000), $review->fresh()->comment);
    }

    public function test_edit_form_cancel_link_goes_back_to_book_detail(): void
    {
        $review = $this->createReview();

        $this->actingAs($this->user)->get(route('reviews.edit', $review))
            ->assertSee('href="' . route('books.show', $this->book) . '"', false);
    }

    public function test_non_author_gets_403_for_edit_and_update_and_nothing_is_saved(): void
    {
        $review = $this->createReview();
        $other = User::factory()->create();

        $this->actingAs($other)->get(route('reviews.edit', $review))->assertForbidden();
        $this->putReview($other, $review, $this->validInput(['rating' => 1, 'comment' => '書き換え']))->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 5, 'comment' => '面白かった']);
    }

    public function test_non_author_sending_invalid_input_gets_validation_error_not_403(): void
    {
        $review = $this->createReview();

        $this->putReview(User::factory()->create(), $review, ['rating' => 9, 'comment' => ''])
            ->assertSessionHasErrors(['rating', 'comment']);

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 5]);
    }

    public function test_guest_is_sent_to_login_for_edit_and_update(): void
    {
        $review = $this->createReview();

        $this->get(route('reviews.edit', $review))->assertRedirect(route('login'));
        $this->put(route('reviews.update', $review), $this->validInput())->assertRedirect(route('login'));
    }

    public function test_edit_and_update_return_404_for_missing_review(): void
    {
        $this->actingAs($this->user)->get('/reviews/9999/edit')->assertNotFound();
        $this->actingAs($this->user)->put('/reviews/9999', $this->validInput())->assertNotFound();
    }

    // ---- 削除（F-13、TP-4-11・4-12） ----

    public function test_author_can_delete_review_and_its_likes_are_deleted_too(): void
    {
        $review = $this->createReview();
        $keep = $this->createReview();
        $fan = User::factory()->create();
        $review->likedByUsers()->attach($fan);
        $keep->likedByUsers()->attach($fan);

        $this->actingAs($this->user)->delete(route('reviews.destroy', $review))
            ->assertRedirect(route('books.show', $this->book))
            ->assertSessionHas('success', 'レビューを削除しました。');

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('review_likes', ['review_id' => $review->id]);
        $this->assertDatabaseHas('reviews', ['id' => $keep->id]);
        $this->assertDatabaseHas('review_likes', ['review_id' => $keep->id]);
    }

    public function test_non_author_cannot_delete_review(): void
    {
        $review = $this->createReview();

        $this->actingAs(User::factory()->create())->delete(route('reviews.destroy', $review))->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_guest_cannot_delete_review(): void
    {
        $review = $this->createReview();

        $this->delete(route('reviews.destroy', $review))->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_destroy_returns_404_for_missing_review(): void
    {
        $this->actingAs($this->user)->delete('/reviews/9999')->assertNotFound();
    }

    // ---- 書籍詳細の出し分け（TP-4-1） ----

    public function test_book_detail_shows_edit_and_delete_only_on_own_reviews(): void
    {
        $mine = $this->createReview(attributes: ['comment' => '自分のレビュー']);
        $theirs = $this->createReview(User::factory()->create(), ['comment' => '他人のレビュー']);

        $this->actingAs($this->user)->get(route('books.show', $this->book))
            ->assertSee('href="' . route('reviews.edit', $mine) . '"', false)
            ->assertDontSee('href="' . route('reviews.edit', $theirs) . '"', false)
            ->assertSee('action="' . route('reviews.destroy', $mine) . '"', false)
            ->assertDontSee('action="' . route('reviews.destroy', $theirs) . '"', false);
    }
}
