<?php

namespace Tests\Feature\Database;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_counts_match_the_requirements(): void
    {
        $this->seed();

        $this->assertSame(5, User::count());
        $this->assertSame(10, Genre::count());
        $this->assertSame(11, Book::count());
        $this->assertSame(32, Review::count());
    }

    public function test_users_are_seeded_with_hashed_password(): void
    {
        $this->seed();

        $yamada = User::where('email', 'yamada@example.com')->firstOrFail();

        $this->assertSame('山田太郎', $yamada->name);
        $this->assertTrue(Hash::check('password', $yamada->password));
        $this->assertSame(
            ['山田太郎', '鈴木花子', '田中一郎', '佐藤美咲', '高橋健太'],
            User::orderBy('id')->pluck('name')->all(),
        );
    }

    public function test_genres_are_seeded_in_order(): void
    {
        $this->seed();

        $this->assertSame(
            ['小説', 'ビジネス', '技術書', '自己啓発', 'エッセイ', '歴史', '科学', '芸術', '料理', '旅行'],
            Genre::orderBy('id')->pluck('name')->all(),
        );
    }

    public function test_books_belong_to_the_first_user_and_have_genres(): void
    {
        $this->seed();

        $yamadaId = User::first()->id;
        $this->assertSame(11, Book::where('user_id', $yamadaId)->count());

        $book = Book::where('isbn', '9784422100524')->firstOrFail();
        $this->assertSame('人を動かす', $book->title);
        $this->assertSame('1936-10-01', $book->published_date);
        $this->assertSame(['ビジネス', '自己啓発'], $book->genres->pluck('name')->sort()->values()->all());
        $this->assertNotEmpty($book->description);
        $this->assertSame('https://placehold.co/200x300/e2e8f0/475569?text=2', $book->image_url);

        $this->assertSame(3, Genre::where('name', '小説')->firstOrFail()->books()->count());
        $this->assertSame(0, Genre::where('name', '料理')->firstOrFail()->books()->count());
    }

    public function test_every_book_has_two_to_four_reviews_with_rating_three_to_five(): void
    {
        $this->seed();

        Book::withCount('reviews')->get()->each(function (Book $book): void {
            $this->assertGreaterThanOrEqual(2, $book->reviews_count);
            $this->assertLessThanOrEqual(4, $book->reviews_count);
        });

        Review::all()->each(function (Review $review): void {
            $this->assertGreaterThanOrEqual(3, $review->rating);
            $this->assertLessThanOrEqual(5, $review->rating);
            $this->assertNotSame('', $review->comment);
        });
    }

    public function test_each_user_has_three_to_five_favorites(): void
    {
        $this->seed();

        User::withCount('favoriteBooks')->get()->each(function (User $user): void {
            $this->assertGreaterThanOrEqual(3, $user->favorite_books_count);
            $this->assertLessThanOrEqual(5, $user->favorite_books_count);
        });
    }

    public function test_each_review_has_zero_to_three_likes_and_never_from_its_author(): void
    {
        $this->seed();

        Review::withCount('likedByUsers')->get()->each(function (Review $review): void {
            $this->assertLessThanOrEqual(3, $review->liked_by_users_count);
            $this->assertFalse($review->likedByUsers()->whereKey($review->user_id)->exists());
        });

        $this->assertDatabaseCount('review_likes', 50);
    }

    public function test_running_the_seeders_twice_does_not_duplicate_rows(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(5, User::count());
        $this->assertSame(10, Genre::count());
        $this->assertSame(11, Book::count());
        $this->assertDatabaseCount('book_genre', 16);
        $this->assertDatabaseCount('favorites', 19);
        $this->assertDatabaseCount('review_likes', 50);
    }
}
