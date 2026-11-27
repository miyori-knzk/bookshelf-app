<?php

namespace App\Http\Resources;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 公開API で返す書籍（`docs/api-spec.md`「書籍オブジェクト」）。
 *
 * `user`・`genres`・`reviews_avg_rating`・`reviews_count` は、呼び出し側で読み込んでおく。
 * `reviews` は、読み込んであるとき（書籍詳細・登録・更新）だけ返す（一覧では返さない）。
 *
 * @mixin Book
 */
class BookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            // 日付型のまま返すと時刻と UTC への変換が付くため、`YYYY-MM-DD` にそろえる。無いときは null（Q-86）
            'published_date' => $this->published_date?->format('Y-m-d'),
            'description' => $this->description,
            'image_url' => $this->image_url,
            'user' => new UserResource($this->user),
            'genres' => GenreResource::collection($this->genres),
            // 平均評価は小数第 1 位に丸める。レビューが無いときは null（Q-58-1）
            'reviews_avg_rating' => $this->reviews_avg_rating === null ? null : round((float) $this->reviews_avg_rating, 1),
            'reviews_count' => $this->reviews_count,
            'reviews' => $this->whenLoaded('reviews', fn () => ReviewResource::collection($this->reviews)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
