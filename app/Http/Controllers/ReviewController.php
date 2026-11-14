<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * レビューの投稿・編集・削除（F-11〜F-13）。
 *
 * どれもルートの `auth` ミドルウェアでログインを確かめる。編集・削除は ReviewPolicy で投稿者だけに許す
 * （`$this->authorize()`）。どの操作も、終わったらそのレビューの書籍詳細へ移動する（Q-44）。
 */
class ReviewController extends Controller
{
    /**
     * レビューを投稿する（F-11）。
     *
     * 同じ人が同じ書籍に何件でも投稿できる（Q-20）。
     */
    public function store(ReviewRequest $request, Book $book): RedirectResponse
    {
        $book->reviews()->create([
            'user_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        return redirect()->route('books.show', $book)->with('success', 'レビューを投稿しました。');
    }

    /**
     * レビュー編集画面を表示する（F-12）。
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', ['review' => $review->load('book')]);
    }

    /**
     * レビューを更新する（F-12）。
     *
     * 入力チェックは認可より先に動く（Q-52）。
     */
    public function update(ReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $review->update($request->validated());

        return redirect()->route('books.show', $review->book_id)->with('success', 'レビューを更新しました。');
    }

    /**
     * レビューを削除する（F-13）。
     *
     * そのレビューへのいいねは、外部キーの連鎖削除で一緒に消える（Q-12）。
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        return redirect()->route('books.show', $review->book_id)->with('success', 'レビューを削除しました。');
    }
}
