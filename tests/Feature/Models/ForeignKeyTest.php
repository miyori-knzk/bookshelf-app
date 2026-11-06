<?php

namespace Tests\Feature\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
