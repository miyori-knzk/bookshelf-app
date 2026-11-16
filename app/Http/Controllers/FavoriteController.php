<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * お気に入りの一覧と切り替え（F-15・F-16）。
 *
 * どちらもルートの `auth` ミドルウェアでログインを確かめる。触るのはログイン中のユーザー自身の行だけ。
 */
class FavoriteController extends Controller
{
    /**
     * お気に入り一覧を表示する（F-16）。
     *
     * お気に入りに追加した日時の新しい順に 10 件ずつ出す。同じ日時のものは `favorites.id` の大きい順（Q-23・Q-50）。
     * 開いたページが最後のページより後ろのときは、書籍がある最後のページへ移動する。お気に入りが 0 件のときは
     * 1 ページ目へ移動する（Q-56）。
     */
    public function index(Request $request): View|RedirectResponse
    {
        $books = $request->user()
            ->favoriteBooks()
            ->orderByPivot('created_at', 'desc')
            ->orderByPivot('id', 'desc')
            ->paginate(10);

        if ($books->currentPage() > $books->lastPage()) {
            return redirect()->route('favorites.index', ['page' => $books->lastPage()]);
        }

        return view('favorites.index', ['books' => $books]);
    }

    /**
     * 書籍のお気に入りを切り替える（F-15）。
     *
     * お気に入り済みなら解除し、まだなら追加する。終わったら、押した画面（書籍詳細またはお気に入り一覧）へ戻る。
     * Referer が無いときは `back()` の標準の動きにして、直前の画面も無ければ `/` へ移動する（Q-45・Q-70）。
     */
    public function toggle(Request $request, Book $book): RedirectResponse
    {
        $request->user()->favoriteBooks()->toggle($book->id);

        return back(fallback: '/');
    }
}
