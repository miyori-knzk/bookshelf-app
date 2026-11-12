<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * ジャンルの一覧・詳細・登録・編集・削除（F-06〜F-10）。
 *
 * ログインの確認はルートの `auth` ミドルウェアで行う。ジャンルに Policy は付けない（Q-16）。
 */
class GenreController extends Controller
{
    /**
     * ジャンル一覧を表示する（F-06）。
     *
     * ページ分けせず、全件を名前順に出す（Q-19）。
     */
    public function index(): View
    {
        $genres = Genre::withCount('books')->orderBy('name')->get();

        return view('genres.index', ['genres' => $genres]);
    }

    /**
     * ジャンル詳細を表示する（F-07）。
     *
     * そのジャンルの書籍を、新しい順に 10 件ずつ出す（Q-19・Q-50）。
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()
            ->with('genres')
            ->orderByDesc('books.created_at')
            ->orderByDesc('books.id')
            ->paginate(10);

        return view('genres.show', ['genre' => $genre, 'books' => $books]);
    }

    /**
     * ジャンル登録画面を表示する（F-08）。
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * ジャンルを登録する（F-08）。
     */
    public function store(StoreGenreRequest $request): RedirectResponse
    {
        Genre::create($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを登録しました。');
    }

    /**
     * ジャンル編集画面を表示する（F-09）。
     */
    public function edit(Genre $genre): View
    {
        return view('genres.edit', ['genre' => $genre]);
    }

    /**
     * ジャンルを更新する（F-09）。
     */
    public function update(UpdateGenreRequest $request, Genre $genre): RedirectResponse
    {
        $genre->update($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを更新しました。');
    }

    /**
     * ジャンルを削除する（F-10）。
     *
     * 書籍が紐付いているジャンルは削除せず、エラーメッセージを付けて一覧へ戻す（Q-17）。
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        if ($genre->books()->exists()) {
            return redirect()->route('genres.index')->with('error', '書籍が登録されているジャンルは削除できません。');
        }

        $genre->delete();

        return redirect()->route('genres.index')->with('success', 'ジャンルを削除しました。');
    }
}
