<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * 書籍。
 */
class Book extends Model
{
    use HasFactory;

    /**
     * 一括代入できる属性。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'author',
        'isbn',
        'published_date',
        'description',
        'image_url',
    ];

    /**
     * 型を変換する属性。
     *
     * 出版日は、書籍詳細と編集のビューが `format()` を呼ぶため、日付にする。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_date' => 'date',
    ];

    /**
     * 登録者。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 書籍に付いているジャンル。
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'book_genre')->withTimestamps();
    }

    /**
     * 書籍のレビュー。
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * キーワードとジャンルで絞り込む（公開API の書籍一覧。F-21）。
     *
     * キーワードは、タイトルか著者に含まれる書籍に絞る（部分一致）。`%`・`_`・`\` は、ワイルドカードや
     * エスケープではなく普通の文字として検索する（Q-55）。ジャンルと両方指定したときは、両方に当てはまる書籍に絞る。
     * どちらも `null` なら、絞り込まない。
     *
     * @param  Builder<Book>  $query
     * @return Builder<Book>
     */
    public function scopeFilter(Builder $query, ?string $keyword, ?int $genreId): Builder
    {
        $like = '%' . addcslashes((string) $keyword, '\\%_') . '%';

        return $query
            ->when($keyword !== null, fn (Builder $q): Builder => $q->where(
                fn (Builder $q): Builder => $q->where('title', 'like', $like)->orWhere('author', 'like', $like)
            ))
            ->when($genreId !== null, fn (Builder $q): Builder => $q->whereHas(
                'genres',
                fn (Builder $q): Builder => $q->where('genres.id', $genreId)
            ));
    }

    /**
     * 公開API の書籍詳細の形に必要なものを、まとめて読み込む（F-22〜F-24）。
     *
     * 登録者・ジャンル・レビュー（投稿者つき、新しい順。同じ日時は ID の大きい順。Q-14・Q-50）と、
     * 平均評価（`reviews_avg_rating`）・レビュー件数（`reviews_count`）。
     */
    public function loadDetail(): self
    {
        $this->load([
            'user',
            'genres',
            'reviews' => fn ($query) => $query->with('user')->orderByDesc('created_at')->orderByDesc('id'),
        ]);
        $this->loadAvg('reviews', 'rating')->loadCount('reviews');

        return $this;
    }

    /**
     * 評価ランキングの書籍を、順位の順に返す（F-17）。
     *
     * レビューが 1 件以上ある書籍だけを対象にする（Q-24）。並び順は、平均評価の高い順、同じならレビュー件数の
     * 多い順、それも同じなら ID の大きい順（Q-24・Q-50）。平均評価が「同じ」かどうかは、画面の星の横の表示と
     * 同じ、小数第 2 位に丸めた値で判定する（Q-72）。
     * 平均評価（`reviews_avg_rating`）とレビュー件数（`reviews_count`）は、集計して付ける。
     *
     * @param  int  $limit  返す件数（TOP10）
     * @return Collection<int, Book>
     */
    public static function ranking(int $limit = 10): Collection
    {
        return self::withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->has('reviews')
            ->get()
            ->sortBy([
                fn (self $a, self $b): int => round($b->reviews_avg_rating, 2) <=> round($a->reviews_avg_rating, 2),
                fn (self $a, self $b): int => $b->reviews_count <=> $a->reviews_count,
                fn (self $a, self $b): int => $b->id <=> $a->id,
            ])
            ->take($limit)
            ->values();
    }

    /**
     * 書籍とジャンルの紐付けを、まとめて登録する（F-03）。
     *
     * 途中で失敗したときに書籍だけが残らないよう、transaction でまとめる（Q-05）。
     *
     * @param  array<string, mixed>  $attributes  書籍の項目（`user_id` を含む）
     * @param  array<int, int|string>  $genreIds  紐付けるジャンルの ID
     */
    public static function createWithGenres(array $attributes, array $genreIds): self
    {
        return DB::transaction(function () use ($attributes, $genreIds): self {
            $book = self::create($attributes);
            $book->genres()->attach($genreIds);

            return $book;
        });
    }

    /**
     * 書籍と、ジャンルの紐付けを、まとめて更新する（F-04）。
     *
     * ジャンルは `sync` で入れ替える。途中で失敗したときに書籍だけ更新されないよう、transaction でまとめる（Q-05）。
     *
     * @param  array<string, mixed>  $attributes  書籍の項目
     * @param  array<int, int|string>  $genreIds  紐付けるジャンルの ID
     */
    public function updateWithGenres(array $attributes, array $genreIds): void
    {
        DB::transaction(function () use ($attributes, $genreIds): void {
            $this->update($attributes);
            $this->genres()->sync($genreIds);
        });
    }
}
