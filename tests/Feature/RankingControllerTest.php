<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 段階 6：評価ランキング（F-17）。
 */
class RankingControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 評価を並べて、レビューを付けた書籍を作る。レビューの投稿者は、書籍ごとに 1 人にそろえる。
     *
     * @param  array<int, int>  $ratings  レビューの評価（1 件ずつ）
     * @param  array<string, mixed>  $attributes  書籍の項目
     */
    private function createBookWithRatings(array $ratings, array $attributes = []): Book
    {
        $book = Book::factory()->create($attributes);
        $reviewer = User::factory()->create();

        collect($ratings)->each(fn (int $rating) => Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'rating' => $rating,
        ]));

        return $book;
    }

    /**
     * 画面に渡された書籍の ID を、順位の順に取り出す。
     *
     * @return array<int, int>
     */
    private function rankedIds(): array
    {
        return $this->get(route('ranking.index'))
            ->viewData('rankedBooks')
            ->pluck('id')
            ->all();
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

    // ---- 表示（TP-13-1） ----

    public function test_guest_can_open_ranking_without_login(): void
    {
        $this->createBookWithRatings([5]);

        $this->get(route('ranking.index'))->assertOk();
    }

    public function test_logged_in_user_sees_the_same_ranking_as_a_guest(): void
    {
        collect(range(1, 3))->each(fn (int $i) => $this->createBookWithRatings([$i + 1]));

        $guestIds = $this->rankedIds();

        $user = User::factory()->create();
        $loggedInIds = $this->actingAs($user)->get(route('ranking.index'))
            ->assertOk()
            ->viewData('rankedBooks')
            ->pluck('id')
            ->all();

        $this->assertSame($guestIds, $loggedInIds);
    }

    public function test_ranking_shows_rank_title_author_average_and_review_count(): void
    {
        $this->createBookWithRatings([5, 4], ['title' => '吾輩は猫である', 'author' => '夏目漱石']);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertSee('吾輩は猫である')
            ->assertSee('夏目漱石')
            ->assertSee('4.50')
            ->assertSee('2件のレビュー');
    }

    public function test_ranking_numbers_start_from_one_in_ranked_order(): void
    {
        // 登録順と順位が逆になるように作る（並べ替えると、元の位置とずれる）
        $this->createBookWithRatings([3], ['title' => '三位の本']);
        $this->createBookWithRatings([4], ['title' => '二位の本']);
        $this->createBookWithRatings([5], ['title' => '一位の本']);

        $response = $this->get(route('ranking.index'))->assertOk();

        // 画面は、コレクションのキーに 1 を足して順位を出す。並べ替えたあとにキーが振り直されていないと、順位がずれる
        $this->assertSame([0, 1, 2], $response->viewData('rankedBooks')->keys()->all());

        preg_match_all('/<!-- 順位 -->\s*<div[^>]*>\s*(\d+)\s*<\/div>.*?<h3[^>]*>\s*(.*?)\s*<\/h3>/s', $response->getContent(), $matches, PREG_SET_ORDER);
        $this->assertSame(
            [['1', '一位の本'], ['2', '二位の本'], ['3', '三位の本']],
            collect($matches)->map(fn (array $match): array => [$match[1], $match[2]])->all(),
        );
    }

    public function test_ranking_cards_link_to_book_detail(): void
    {
        $book = $this->createBookWithRatings([5]);

        $this->get(route('ranking.index'))
            ->assertSee(route('books.show', $book), false);
    }

    // ---- 並び順（TP-13-2） ----

    public function test_ranking_orders_by_average_rating_desc(): void
    {
        $low = $this->createBookWithRatings([3, 3]);
        $high = $this->createBookWithRatings([5, 5]);
        $middle = $this->createBookWithRatings([4, 4]);

        $this->assertSame([$high->id, $middle->id, $low->id], $this->rankedIds());
    }

    public function test_ranking_orders_same_average_by_review_count_desc(): void
    {
        $few = $this->createBookWithRatings([4, 4]);
        $many = $this->createBookWithRatings([4, 4, 4, 4]);
        $middle = $this->createBookWithRatings([4, 4, 4]);

        $this->assertSame([$many->id, $middle->id, $few->id], $this->rankedIds());
    }

    public function test_ranking_orders_same_average_and_count_by_id_desc(): void
    {
        $first = $this->createBookWithRatings([4, 5]);
        $second = $this->createBookWithRatings([4, 5]);
        $third = $this->createBookWithRatings([4, 5]);

        $this->assertSame([$third->id, $second->id, $first->id], $this->rankedIds());
    }

    public function test_ranking_treats_averages_equal_to_two_decimal_places_as_the_same(): void
    {
        // 平均 13/3（4.3333）。小数第 2 位に丸めると 4.33
        $fewReviews = $this->createBookWithRatings([4, 4, 5]);
        // 平均 199/46（4.3261）。小数第 2 位に丸めると 4.33。丸めずに比べると、上の書籍より低い
        $manyReviews = $this->createBookWithRatings([
            ...array_fill(0, 15, 5),
            ...array_fill(0, 31, 4),
        ]);

        // 丸めた値は同じなので、レビュー件数の多い書籍が上になる（Q-72）
        $this->assertSame([$manyReviews->id, $fewReviews->id], $this->rankedIds());
    }

    public function test_ranking_puts_higher_average_above_lower_even_with_fewer_reviews(): void
    {
        $manyReviews = $this->createBookWithRatings([4, 4, 4, 4]);
        $fewReviews = $this->createBookWithRatings([5]);

        $this->assertSame([$fewReviews->id, $manyReviews->id], $this->rankedIds());
    }

    // ---- 対象（TP-13-3） ----

    public function test_ranking_excludes_books_without_reviews(): void
    {
        $reviewed = $this->createBookWithRatings([3]);
        $unreviewed = Book::factory()->create(['title' => 'レビューの無い本']);

        $this->assertSame([$reviewed->id], $this->rankedIds());
        $this->get(route('ranking.index'))->assertDontSee($unreviewed->title);
    }

    // ---- 件数（TP-13-4） ----

    public function test_ranking_shows_all_ten_when_there_are_ten_books(): void
    {
        collect(range(1, 10))->each(fn () => $this->createBookWithRatings([4]));

        $this->assertCount(10, $this->rankedIds());
    }

    public function test_ranking_shows_only_the_top_ten_when_there_are_eleven_books(): void
    {
        $lowest = $this->createBookWithRatings([1]);
        collect(range(1, 10))->each(fn () => $this->createBookWithRatings([5]));

        $ids = $this->rankedIds();

        $this->assertCount(10, $ids);
        $this->assertNotContains($lowest->id, $ids);
    }

    // ---- 0 件（TP-13-5） ----

    public function test_ranking_shows_message_when_no_book_has_reviews(): void
    {
        Book::factory()->create();

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertSee('まだレビューが投稿された書籍がありません。');
    }

    // ---- N+1（TP-13-6） ----

    public function test_ranking_query_count_does_not_grow_with_book_count(): void
    {
        $this->createBookWithRatings([5]);
        $few = $this->countQueries(fn () => $this->get(route('ranking.index'))->assertOk());

        collect(range(1, 9))->each(fn () => $this->createBookWithRatings([4, 3]));
        $many = $this->countQueries(fn () => $this->get(route('ranking.index'))->assertOk());

        $this->assertSame($few, $many);
    }
}
