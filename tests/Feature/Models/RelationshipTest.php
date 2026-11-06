<?php

namespace Tests\Feature\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_relations(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();
        $review = Review::factory()->create(['book_id' => $book->id]);
        $book->genres()->attach($genre);

        $this->assertTrue($book->user->is(User::find($book->user_id)));
        $this->assertTrue($book->genres->first()->is($genre));
        $this->assertTrue($book->reviews->first()->is($review));
    }

    public function test_genre_books_relation(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();
        $genre->books()->attach($book);

        $this->assertTrue($genre->books->first()->is($book));
    }

    public function test_review_relations(): void
    {
        $review = Review::factory()->create();
        $liker = User::factory()->create();
        $review->likedByUsers()->attach($liker);

        $this->assertTrue($review->user->is(User::find($review->user_id)));
        $this->assertTrue($review->book->is(Book::find($review->book_id)));
        $this->assertTrue($review->likedByUsers->first()->is($liker));
    }

    public function test_user_favorite_books_and_liked_reviews(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create();
        $user->favoriteBooks()->attach($book);
        $user->likedReviews()->attach($review);

        $this->assertTrue($user->favoriteBooks->first()->is($book));
        $this->assertTrue($user->likedReviews->first()->is($review));
    }

    public function test_pivot_tables_record_timestamps(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create();
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre);
        $user->favoriteBooks()->attach($book);
        $user->likedReviews()->attach($review);

        collect([
            $book->genres()->first()->pivot,
            $user->favoriteBooks()->first()->pivot,
            $user->likedReviews()->first()->pivot,
        ])->each(function ($pivot): void {
            $this->assertNotNull($pivot->created_at);
            $this->assertNotNull($pivot->updated_at);
        });
    }
}
