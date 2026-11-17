<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * レビューのいいね（F-14）。
 *
 * ルートの `auth` ミドルウェアでログインを確かめる。自分のレビューにもいいねできる（Q-22）。
 */
class ReviewLikeController extends Controller
{
    /**
     * レビューのいいねを切り替える（F-14）。
     *
     * いいね済みなら取り消し、まだなら追加する。終わったら、押した画面（書籍詳細）へ戻る。
     * Referer が無いときは `back()` の標準の動きにして、直前の画面も無ければ `/` へ移動する（Q-45・Q-70）。
     */
    public function toggle(Request $request, Review $review): RedirectResponse
    {
        $request->user()->likedReviews()->toggle($review->id);

        return back(fallback: '/');
    }
}
