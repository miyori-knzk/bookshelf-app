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
| ルートを足す・変えるときは、tests/Feature/RouteDefinitionTest.php の
| routeProvider() に、メソッド・URI・ルート名を足す。
| ルート名が `api.` で始まるものは、authRouteProvider() が自動で除くので、
| そちらは直さなくてよい。
|
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::apiResource('books', BookController::class);
});
