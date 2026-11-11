# ルーティング

Web と公開API のルートをまとめたもの。範囲は基本機能のみ。
誰が使えるかは `docs/permission-matrix.md`、公開API の入出力は `docs/api-spec.md` を参照。

クラス図は作らない。Laravel の MVC の標準の作り（ルート → コントローラー → FormRequest・Policy → モデル → ビュー・API Resource）に乗せる。

## 共通

| 項目               | 内容                                                                                       | 根拠 |
| ------------------ | ------------------------------------------------------------------------------------------ | ---- |
| ルート名           | ビューが `route()` で呼んでいる名前に合わせる                                               | 各ビュー |
| URI                | 画面のある操作は要件の画面定義、画面の無い操作は要件シートのとおり                           | 要件「画面定義」、Q-25 |
| モデルの受け取り   | URI の `{book}`・`{genre}`・`{review}` は ID で、モデルに自動で変換する（ルートモデル結合）。見つからないときは 404 | `docs/error-handling.md` |
| 認証               | ログインが必要なルートには `auth` ミドルウェアを付ける                                      | `docs/permission-matrix.md` |
| 認可               | ミドルウェアでは行わず、コントローラーの `$this->authorize()` で行う                        | 要件「Policy」 |
| コントローラー名   | どれも案                                                                                   | － |

## Web（`routes/web.php`）

### 書籍

| メソッド | URI                  | ルート名        | コントローラー           | 認証 | 認可                   | 機能 |
| -------- | -------------------- | --------------- | ------------------------ | ---- | ---------------------- | ---- |
| GET      | `/`                  | `books.index`   | `BookController@index`   |      |                        | F-01 |
| GET      | `/books/create`      | `books.create`  | `BookController@create`  | 要   |                        | F-03 |
| POST     | `/books`             | `books.store`   | `BookController@store`   | 要   |                        | F-03 |
| GET      | `/books/{book}`      | `books.show`    | `BookController@show`    |      |                        | F-02 |
| GET      | `/books/{book}/edit` | `books.edit`    | `BookController@edit`    | 要   | `BookPolicy::update`   | F-04 |
| PUT      | `/books/{book}`      | `books.update`  | `BookController@update`  | 要   | `BookPolicy::update`   | F-04 |
| DELETE   | `/books/{book}`      | `books.destroy` | `BookController@destroy` | 要   | `BookPolicy::delete`   | F-05 |

- 書籍一覧は `/` に置き、ルート名を `books.index` にする（Q-01）。`GET /books` は作らない（Q-01）。
- `/books/create` は `/books/{book}` より先に登録する。後に登録すると、`create` が書籍 ID とみなされて 404 になる。
- `Route::resource()` は、`PUT` のほかに `PATCH` も `update` につなげる（書籍・ジャンル）。ビューのフォームは `PUT` を送る。
- `GET /books` は無いが、`POST /books` があるため、`GET /books` を開くと 404 ではなく 405 になる。

### ジャンル

| メソッド | URI                    | ルート名         | コントローラー            | 認証 | 認可 | 機能 |
| -------- | ---------------------- | ---------------- | ------------------------- | ---- | ---- | ---- |
| GET      | `/genres`              | `genres.index`   | `GenreController@index`   | 要   |      | F-06 |
| GET      | `/genres/create`       | `genres.create`  | `GenreController@create`  | 要   |      | F-08 |
| POST     | `/genres`              | `genres.store`   | `GenreController@store`   | 要   |      | F-08 |
| GET      | `/genres/{genre}`      | `genres.show`    | `GenreController@show`    | 要   |      | F-07 |
| GET      | `/genres/{genre}/edit` | `genres.edit`    | `GenreController@edit`    | 要   |      | F-09 |
| PUT      | `/genres/{genre}`      | `genres.update`  | `GenreController@update`  | 要   |      | F-09 |
| DELETE   | `/genres/{genre}`      | `genres.destroy` | `GenreController@destroy` | 要   |      | F-10 |

- すべて認証が必要なため、`Route::resource('genres', ...)` を `auth` のグループに入れれば、この 7 本になる。
- ジャンルには Policy を付けない（Q-16）。

### レビュー・いいね・お気に入り・ランキング

| メソッド | URI                        | ルート名           | コントローラー                | 認証 | 認可                     | 機能 |
| -------- | -------------------------- | ------------------ | ----------------------------- | ---- | ------------------------ | ---- |
| POST     | `/books/{book}/reviews`    | `reviews.store`    | `ReviewController@store`      | 要   |                          | F-11 |
| GET      | `/reviews/{review}/edit`   | `reviews.edit`     | `ReviewController@edit`       | 要   | `ReviewPolicy::update`   | F-12 |
| PUT      | `/reviews/{review}`        | `reviews.update`   | `ReviewController@update`     | 要   | `ReviewPolicy::update`   | F-12 |
| DELETE   | `/reviews/{review}`        | `reviews.destroy`  | `ReviewController@destroy`    | 要   | `ReviewPolicy::delete`   | F-13 |
| POST     | `/reviews/{review}/like`   | `reviews.like`     | `ReviewLikeController@toggle` | 要   |                          | F-14 |
| GET      | `/favorites`               | `favorites.index`  | `FavoriteController@index`    | 要   |                          | F-16 |
| POST     | `/books/{book}/favorites`  | `favorites.toggle` | `FavoriteController@toggle`   | 要   |                          | F-15 |
| GET      | `/ranking`                 | `ranking.index`    | `RankingController@index`     |      |                          | F-17 |

- URI は要件シートのとおり（Q-25）。お気に入りは複数形の `/favorites`。

### 認証（Fortify）

Fortify が登録するルートをそのまま使う。自分では書かない。

| メソッド | URI         | ルート名   | ミドルウェア             | 機能 |
| -------- | ----------- | ---------- | ------------------------ | ---- |
| GET      | `/login`    | `login`    | `guest`                  | F-19 |
| POST     | `/login`    | `login.store` | `guest`・ログイン回数の制限 | F-19 |
| GET      | `/register` | `register` | `guest`                  | F-18 |
| POST     | `/register` | `register.store` | `guest`                  | F-18 |
| POST     | `/logout`   | `logout`   | `auth`                   | F-20 |

- ビューのフォームは `route('login')`・`route('register')` で URI を作り、その URI へ POST する（`auth/login`・`auth/register`）。
- `guest` ミドルウェアは、ログイン中なら `RouteServiceProvider::HOME` へ移動させる。Laravel の初期値は `/home` で、このルートは無いため、`/` に変える（Q-08）。ログインと会員登録の後の移動先（`config/fortify.php` の `home`）も、`RouteServiceProvider::HOME` を参照して `/` にする。
- ログアウトの後は、ログイン画面へ移動する（Q-08）。`config/fortify.php` の `redirects` に `logout` を指定する。
- ログインの後は、開こうとした画面があればそこへ、なければ書籍一覧へ移動する（Fortify の標準、Q-10）。会員登録の後は、開こうとした画面があっても、常に書籍一覧へ移動する（Q-61）。標準の動きと違うため、`App\Http\Responses\RegisterResponse` で `RegisterResponse` を差し替える。
- ログイン画面と会員登録画面は、`FortifyServiceProvider` の `Fortify::loginView()`・`Fortify::registerView()` で、`auth/login`・`auth/register` を返す。
- パスワードリセット・メール確認・プロフィール更新・パスワード変更・2 段階認証は `features` から外す（Q-29〜Q-31・Q-38）。これらのルートは登録されない。

## 公開API（`routes/api.php`）

入出力は `docs/api-spec.md` を参照。

| メソッド | URI                    | ルート名               | コントローラー                    | 認証 | 機能 |
| -------- | ---------------------- | ---------------------- | --------------------------------- | ---- | ---- |
| GET      | `/api/v1/books`        | `api.v1.books.index`   | `Api\V1\BookController@index`     |      | F-21 |
| GET      | `/api/v1/books/{book}` | `api.v1.books.show`    | `Api\V1\BookController@show`      |      | F-22 |
| POST     | `/api/v1/books`        | `api.v1.books.store`   | `Api\V1\BookController@store`     |      | F-23 |
| PUT      | `/api/v1/books/{book}` | `api.v1.books.update`  | `Api\V1\BookController@update`    |      | F-24 |
| DELETE   | `/api/v1/books/{book}` | `api.v1.books.destroy` | `Api\V1\BookController@destroy`   |      | F-25 |

- `routes/api.php` のルートには、Laravel が `/api` を付け、`api` ミドルウェアグループ（1 分に 60 回までの回数制限など）をかける。ここでは `v1` を付ける。
- ルート名は `api.v1.` から始める。付けないと `Route::apiResource()` の名前が Web の `books.index` などと同じになり、ビューの `route('books.index')` がどちらを指すかが、ルートを読み込む順番で決まってしまう。
- `Route::apiResource()` は `PATCH /api/v1/books/{book}` も作り、`update` につながる。入力チェックは `PUT` と同じ（すべての項目を送る）。
- Laravel 初期の `GET /api/user`（`auth:sanctum`）は使わないため消す。

## 作らないルート

| ルート                                     | 理由                                      |
| ------------------------------------------ | ----------------------------------------- |
| `GET /books`                               | 書籍一覧は `/` に置く（Q-01）   |
| `GET /reviews`・`GET /reviews/{review}` など | レビューの一覧・詳細画面は無い。レビューは書籍詳細に表示する |
| `GET /api/user`                            | 基本機能の API は認証しない（Q-35）        |
| ★応用機能のルート（`/reports`・`/reading-plans`・`/notifications` など） | 今回の範囲外（`docs/features.md`） |
