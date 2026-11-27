<?php

namespace Tests\Feature\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ForeignKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_book_deletes_its_related_rows(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->create(['book_id' => $book->id]);
        $user = User::factory()->create();
        $book->genres()->attach(Genre::factory()->create());
        $user->favoriteBooks()->attach($book);
        $user->likedReviews()->attach($review);

        $book->delete();

        $this->assertDatabaseCount('book_genre', 0);
        $this->assertDatabaseCount('favorites', 0);
        $this->assertSame(0, Review::count());
        $this->assertDatabaseCount('review_likes', 0);
    }

    public function test_deleting_a_review_deletes_its_likes(): void
    {
        $review = Review::factory()->create();
        User::factory()->create()->likedReviews()->attach($review);

        $review->delete();

        $this->assertDatabaseCount('review_likes', 0);
    }

    public function test_a_genre_with_books_cannot_be_deleted(): void
    {
        $genre = Genre::factory()->create();
        Book::factory()->create()->genres()->attach($genre);

        $this->expectException(QueryException::class);

        $genre->delete();
    }

    public function test_a_user_with_books_cannot_be_deleted(): void
    {
        $book = Book::factory()->create();

        $this->expectException(QueryException::class);

        User::find($book->user_id)->delete();
    }

    public function test_a_user_with_reviews_cannot_be_deleted(): void
    {
        $review = Review::factory()->create();

        $this->expectException(QueryException::class);

        User::find($review->user_id)->delete();
    }

    public function test_the_same_favorite_cannot_be_added_twice(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $user->favoriteBooks()->attach($book);

        $this->expectException(QueryException::class);

        $user->favoriteBooks()->attach($book);
    }

    public function test_the_same_like_cannot_be_added_twice(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();
        $user->likedReviews()->attach($review);

        $this->expectException(QueryException::class);

        $user->likedReviews()->attach($review);
    }

    public function test_the_same_genre_cannot_be_attached_to_a_book_twice(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();
        $book->genres()->attach($genre);

        $this->expectException(QueryException::class);

        $book->genres()->attach($genre);
    }

    public function test_isbn_must_be_unique(): void
    {
        $book = Book::factory()->create();

        $this->expectException(QueryException::class);

        Book::factory()->create(['isbn' => $book->isbn]);
    }

    public function test_genre_name_must_be_unique(): void
    {
        Genre::factory()->create(['name' => '小説']);

        $this->expectException(QueryException::class);

        Genre::factory()->create(['name' => '小説']);
    }

    public function test_the_same_user_can_review_the_same_book_more_than_once(): void
    {
        $review = Review::factory()->create();

        Review::factory()->create(['user_id' => $review->user_id, 'book_id' => $review->book_id]);

        $this->assertSame(2, Review::count());
    }

    public function test_isbn_and_published_date_can_be_null(): void
    {
        Book::factory()->count(2)->create(['isbn' => null, 'published_date' => null]);

        $this->assertSame(2, Book::whereNull('isbn')->whereNull('published_date')->count());
    }

    public function test_a_book_with_reading_plans_cannot_be_deleted(): void
    {
        $book = Book::factory()->create();
        $this->insertReadingPlan(User::factory()->create(), $book, ['status' => 'completed']);

        $this->expectException(QueryException::class);

        $book->delete();
    }

    public function test_a_user_with_reading_plans_cannot_be_deleted(): void
    {
        $book = Book::factory()->create();
        $user = User::factory()->create();
        $this->insertReadingPlan($user, $book);

        $this->expectException(QueryException::class);

        $user->delete();
    }

    public function test_a_reading_plan_is_in_progress_by_default(): void
    {
        $this->insertReadingPlan(User::factory()->create(), Book::factory()->create());

        $this->assertDatabaseHas('reading_plans', ['status' => 'in_progress', 'completed_at' => null]);
    }

    public function test_a_reading_plan_accepts_completed_and_expired_status(): void
    {
        $user = User::factory()->create();

        $this->insertReadingPlan($user, Book::factory()->create(), ['status' => 'completed']);
        $this->insertReadingPlan($user, Book::factory()->create(), ['status' => 'expired']);

        $this->assertDatabaseCount('reading_plans', 2);
    }

    public function test_a_reading_plan_rejects_an_unknown_status(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->expectException(QueryException::class);

        $this->insertReadingPlan($user, $book, ['status' => 'unknown']);
    }

    public function test_the_same_user_cannot_plan_the_same_book_twice(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $this->insertReadingPlan($user, $book, ['status' => 'completed']);

        $this->expectException(QueryException::class);

        $this->insertReadingPlan($user, $book);
    }

    public function test_different_users_can_plan_the_same_book(): void
    {
        $book = Book::factory()->create();

        $this->insertReadingPlan(User::factory()->create(), $book);
        $this->insertReadingPlan(User::factory()->create(), $book);

        $this->assertDatabaseCount('reading_plans', 2);
    }

    public function test_a_notification_can_be_saved_for_a_user(): void
    {
        $user = User::factory()->create();

        $user->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => 'test',
            'data' => ['title' => '件名'],
        ]);

        $this->assertSame(['title' => '件名'], $user->unreadNotifications()->firstOrFail()->data);
    }

    /**
     * 読書計画の行を作る。`ReadingPlan` モデルを作る段階 12 で、Eloquent に書き換える。
     *
     * @param  array<string, mixed>  $attributes  上書きする列
     */
    private function insertReadingPlan(User $user, Book $book, array $attributes = []): void
    {
        DB::table('reading_plans')->insert(array_merge([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-12-01',
        ], $attributes));
    }
}
