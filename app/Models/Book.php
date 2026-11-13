<?php

namespace App\Models;

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
