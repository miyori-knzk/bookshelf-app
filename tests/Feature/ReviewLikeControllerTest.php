<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * 段階 5：レビューのいいね（F-14）。
 */
class ReviewLikeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Book $book;

    private Review $review;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->book = Book::factory()->create();
        $this->review = Review::factory()->create(['book_id' => $this->book->id]);
    }

    /**
     * 押した画面（Referer）を付けて、いいねの切り替えを送る。
     */
    private function toggle(User $user, Review $review, string $referer): TestResponse
    {
        return $this->actingAs($user)
            ->withHeader('Referer', $referer)
            ->post(route('reviews.like', $review));
    }

    public function test_toggle_adds_a_like_and_returns_to_book_detail(): void
    {
        $detail = route('books.show', $this->book);

        $this->toggle($this->user, $this->review, $detail)->assertRedirect($detail);

        $this->assertDatabaseHas('review_likes', ['user_id' => $this->user->id, 'review_id' => $this->review->id]);
    }

    public function test_toggle_removes_the_like_when_pressed_again(): void
    {
        $detail = route('books.show', $this->book);

        $this->toggle($this->user, $this->review, $detail);
        $this->toggle($this->user, $this->review, $detail)->assertRedirect($detail);

        $this->assertDatabaseMissing('review_likes', ['user_id' => $this->user->id, 'review_id' => $this->review->id]);
    }

    public function test_toggle_changes_the_button_and_count_on_book_detail(): void
    {
        $detail = route('books.show', $this->book);

        $this->actingAs($this->user)->get($detail)->assertSee('いいね (0)')->assertDontSee('いいね済み');

        $this->toggle($this->user, $this->review, $detail);

        // 1 回目の表示で読み込んだ likedReviews が残らないよう、新しく読み直したユーザーで開く
        $this->actingAs(User::find($this->user->id))->get($detail)->assertSee('いいね済み (1)');

        $this->toggle($this->user, $this->review, $detail);

        $this->actingAs(User::find($this->user->id))->get($detail)->assertSee('いいね (0)')->assertDontSee('いいね済み');
    }

    public function test_user_can_like_their_own_review(): void
    {
        $own = Review::factory()->create(['book_id' => $this->book->id, 'user_id' => $this->user->id]);

        $this->toggle($this->user, $own, route('books.show', $this->book));

        $this->assertDatabaseHas('review_likes', ['user_id' => $this->user->id, 'review_id' => $own->id]);
    }

    public function test_likes_of_other_users_are_counted_and_kept(): void
    {
        $other = User::factory()->create();
        $other->likedReviews()->attach($this->review->id);

        $this->toggle($this->user, $this->review, route('books.show', $this->book));

        $this->assertDatabaseHas('review_likes', ['user_id' => $other->id, 'review_id' => $this->review->id]);
        $this->assertDatabaseCount('review_likes', 2);
        $this->actingAs(User::find($this->user->id))->get(route('books.show', $this->book))->assertSee('いいね済み (2)');
    }

    public function test_toggle_only_affects_the_given_review(): void
    {
        $another = Review::factory()->create(['book_id' => $this->book->id]);

        $this->toggle($this->user, $this->review, route('books.show', $this->book));

        $this->assertDatabaseMissing('review_likes', ['review_id' => $another->id]);
    }

    public function test_toggle_returns_to_the_previous_page_in_the_session_when_there_is_no_referer(): void
    {
        $detail = route('books.show', $this->book);

        $this->actingAs($this->user)->from($detail)->post(route('reviews.like', $this->review))->assertRedirect($detail);
    }

    public function test_toggle_goes_to_top_page_when_there_is_no_referer_and_no_previous_page(): void
    {
        $this->actingAs($this->user)->post(route('reviews.like', $this->review))->assertRedirect('/');

        $this->assertDatabaseHas('review_likes', ['user_id' => $this->user->id, 'review_id' => $this->review->id]);
    }

    public function test_toggle_by_guest_is_sent_to_login_and_nothing_is_saved(): void
    {
        $this->post(route('reviews.like', $this->review))->assertRedirect(route('login'));

        $this->assertDatabaseCount('review_likes', 0);
    }

    public function test_toggle_returns_404_for_missing_review(): void
    {
        $this->actingAs($this->user)->post('/reviews/9999/like')->assertNotFound();
    }
}
