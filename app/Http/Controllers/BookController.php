<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧を表示する（F-01）。
     *
     * 仮実装。段階 3 で、書籍の取得とページ送りを実装する。
     */
    public function index(): View
    {
        return view('books.index', ['books' => collect()]);
    }

    /**
     * 書籍詳細を表示する（F-02）。
     *
     * 仮実装。段階 3 で実装する。
     */
    public function show(): never
    {
        abort(501);
    }

    /**
     * 書籍登録画面を表示する（F-03）。
     *
     * 仮実装。段階 3 で実装する。
     */
    public function create(): never
    {
        abort(501);
    }

    /**
     * 書籍を登録する（F-03）。
     *
     * 仮実装。段階 3 で実装する。
     */
    public function store(): never
    {
        abort(501);
    }

    /**
     * 書籍編集画面を表示する（F-04）。
     *
     * 仮実装。段階 3 で実装する。
     */
    public function edit(): never
    {
        abort(501);
    }

    /**
     * 書籍を更新する（F-04）。
     *
     * 仮実装。段階 3 で実装する。
     */
    public function update(): never
    {
        abort(501);
    }

    /**
     * 書籍を削除する（F-05）。
     *
     * 仮実装。段階 3 で実装する。
     */
    public function destroy(): never
    {
        abort(501);
    }
}
