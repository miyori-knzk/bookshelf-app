<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

/**
 * 公開API の書籍の一覧・詳細・登録・更新・削除（F-21〜F-25）。
 *
 * 基本機能では認証も認可もしない（誰でも、どの書籍でも更新・削除できる。`docs/permission-matrix.md`）。
 * エラーの JSON は、例外ハンドラー（`app/Exceptions/Handler.php`）が作る。
 */
class BookController extends Controller
{
    /**
     * 1 ページの件数の初期値（Q-27）。
     */
    private const DEFAULT_PER_PAGE = 20;

    /**
     * 書籍の一覧を JSON で返す（F-21）。
     *
     * キーワード・ジャンルで絞り込み、登録日時の新しい順に並べる。同じ日時のものは ID の大きい順（Q-27・Q-50）。
     * 次のページなどの URL（`links`）には、絞り込みと 1 ページの件数の条件を引き継ぐ（Q-73）。
     */
    public function index(IndexBookRequest $request): AnonymousResourceCollection
    {
        $books = Book::with(['user', 'genres'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->filter($request->validated('keyword'), $request->validated('genre_id'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($request->validated('per_page') ?? self::DEFAULT_PER_PAGE))
            ->appends(
                collect($request->only(['keyword', 'genre_id', 'per_page']))
                    ->filter(fn (mixed $value): bool => filled($value))
                    ->all()
            );

        return BookResource::collection($books);
    }

    /**
     * 書籍の詳細を JSON で返す（F-22）。存在しない ID は 404（例外ハンドラーが返す）。
     */
    public function show(Book $book): BookResource
    {
        return new BookResource($book->loadDetail());
    }

    /**
     * 書籍を登録する（F-23）。
     *
     * 登録者は、リクエストの `user_id`（Q-26）。登録した書籍を、書籍詳細と同じ形で 201 で返す（Q-57）。
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $book = Book::createWithGenres(Arr::except($validated, 'genres'), $validated['genres']);

        return (new BookResource($book->loadDetail()))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * 書籍を更新する（F-24）。
     *
     * `user_id` は、送られたときだけ登録者を変える（Q-41）。更新後の書籍を、書籍詳細と同じ形で返す（Q-57）。
     */
    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        $validated = $request->validated();

        $book->updateWithGenres(Arr::except($validated, 'genres'), $validated['genres']);

        return new BookResource($book->loadDetail());
    }

    /**
     * 書籍を削除する（F-25）。
     *
     * 紐付くジャンル・お気に入り・レビュー・いいねは、外部キーの連鎖削除で一緒に消える（Q-12）。本文なしの 204。
     */
    public function destroy(Book $book): Response
    {
        $book->delete();

        return response()->noContent();
    }
}
