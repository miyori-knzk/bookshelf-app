<?php

use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| ルートの一覧は docs/routing.md を参照。
| `/api` は RouteServiceProvider が付ける。基本機能では認証しない。
| ルート名を `api.v1.` から始めるのは、Web の `books.index` などと名前が重ならないようにするため。
|
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::apiResource('books', BookController::class);
});
