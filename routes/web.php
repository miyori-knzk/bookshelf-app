<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReportController;
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

// ログインが必要なルート
// `/books/{book}` より先に登録する（後だと `/books/create` が書籍 ID とみなされて 404 になる）
Route::middleware('auth')->group(function (): void {
    // 書籍
    Route::resource('books', BookController::class)->except(['index', 'show']);
    Route::get('/books/isbn/{isbn}', [BookController::class, 'searchByIsbn'])->name('books.isbn'); // 本体は段階 11

    // ジャンル
    Route::resource('genres', GenreController::class);

    // レビュー・いいね
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'toggle'])->name('reviews.like');

    // お気に入り
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // 読書計画（本体は段階 12）
    Route::get('/reading-plans', [ReadingPlanController::class, 'index'])->name('reading-plans.index');
    Route::get('/reading-plans/create', [ReadingPlanController::class, 'create'])->name('reading-plans.create');
    Route::post('/reading-plans', [ReadingPlanController::class, 'store'])->name('reading-plans.store');
    Route::get('/reading-plans/{plan}/edit', [ReadingPlanController::class, 'edit'])->name('reading-plans.edit');
    Route::put('/reading-plans/{plan}', [ReadingPlanController::class, 'update'])->name('reading-plans.update');
    Route::delete('/reading-plans/{plan}', [ReadingPlanController::class, 'destroy'])->name('reading-plans.destroy');
    Route::post('/reading-plans/{plan}/complete', [ReadingPlanController::class, 'complete'])->name('reading-plans.complete');

    // 通知（本体は段階 13）
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // マイ読書レポート（本体は段階 14）
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});

// ログインが要らないルート
// 書籍。一覧は `/` に置く（GET /books は作らない）
Route::get('/', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

// 評価ランキング
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');
