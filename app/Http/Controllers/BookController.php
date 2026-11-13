<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

/**
 * 書籍の一覧・詳細・登録・編集・削除（F-01〜F-05）。
 *
 * 一覧・詳細はログイン不要。登録・編集・削除はルートの `auth` ミドルウェアでログインを確かめ、
 * 編集・削除は BookPolicy で作成者だけに許す（`$this->authorize()`）。
 */
class BookController extends Controller
{
    /**
     * 書籍一覧を表示する（F-01）。
     *
     * 登録日時の新しい順に 10 件ずつ出す。同じ日時のものは ID の大きい順（Q-13・Q-50）。
     */
    public function index(): View
    {
        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('books.index', ['books' => $books]);
    }

    /**
     * 書籍詳細を表示する（F-02）。
     *
     * レビューは、投稿の新しい順に全件出す（Q-14・Q-50）。
     */
    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews' => fn ($query) => $query
                ->with(['user', 'likedByUsers'])
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
        ]);

        return view('books.show', ['book' => $book]);
    }

    /**
     * 書籍登録画面を表示する（F-03）。
     */
    public function create(): View
    {
        return view('books.create', ['genres' => Genre::orderBy('name')->get()]);
    }

    /**
     * 書籍を登録する（F-03）。
     *
     * 登録したら、その書籍の詳細へ移動する（Q-15）。
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $book = Book::createWithGenres(
            ['user_id' => $request->user()->id, ...Arr::except($validated, 'genres')],
            $validated['genres'],
        );

        return redirect()->route('books.show', $book)->with('success', '書籍を登録しました。');
    }

    /**
     * 書籍編集画面を表示する（F-04）。
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        return view('books.edit', [
            'book' => $book->load('genres'),
            'genres' => Genre::orderBy('name')->get(),
        ]);
    }

    /**
     * 書籍を更新する（F-04）。
     *
     * 入力チェックは認可より先に動く（Q-52）。更新したら、その書籍の詳細へ移動する（Q-15）。
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $book->updateWithGenres(Arr::except($validated, 'genres'), $validated['genres']);

        return redirect()->route('books.show', $book)->with('success', '書籍を更新しました。');
    }

    /**
     * 書籍を削除する（F-05）。
     *
     * 紐付くジャンル・お気に入り・レビュー・いいねは、外部キーの連鎖削除で一緒に消える（Q-12）。
     * 削除したら、書籍一覧へ移動する（Q-15）。
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()->route('books.index')->with('success', '書籍を削除しました。');
    }
}
