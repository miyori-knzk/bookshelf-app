<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Contracts\View\View;

/**
 * 評価ランキング（F-17）。
 *
 * ログイン不要。
 */
class RankingController extends Controller
{
    /**
     * 評価ランキングを表示する（F-17）。
     *
     * レビューの平均評価の TOP10 を出す。対象と並び順は `Book::ranking()`（Q-24・Q-50・Q-72）。
     */
    public function index(): View
    {
        return view('ranking.index', ['rankedBooks' => Book::ranking()]);
    }
}
