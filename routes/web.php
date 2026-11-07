<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| ルートの一覧は docs/routing.md を参照。
| 認可（Policy）はミドルウェアではなく、コントローラーの authorize() で行う。
|
| ルートを足す・変えるときは、tests/Feature/RouteDefinitionTest.php も直す。
| - routeProvider() に、メソッド・URI・ルート名を足す
| - ログインが要らないルートを足すときは、authRouteProvider() の
|   最初の reject の一覧（ログイン不要のルート名）にも足す。足し忘れると、
|   「ゲストはログイン画面へ移動する」のテストが失敗する
|
*/

// 書籍。一覧は `/` に置く（GET /books は作らない）
Route::get('/', [BookController::class, 'index'])->name('books.index');

// `/books/create` を `/books/{book}` より先に登録する（後だと create が書籍 ID とみなされて 404 になる）
Route::resource('books', BookController::class)
    ->except(['index', 'show'])
    ->middleware('auth');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

// ジャンル（すべてログインが必要）
Route::middleware('auth')->group(function (): void {
    Route::resource('genres', GenreController::class);
});

// レビュー・いいね・お気に入り
Route::middleware('auth')->group(function (): void {
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'toggle'])->name('reviews.like');

    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
});

// 評価ランキング
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');
