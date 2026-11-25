# 機能一覧

`docs/requirements.md`（以下「要件」）と `resources/views/` 配下のビュー（以下「ビュー」）を突き合わせて整理したもの。
基本機能（段階 0〜8）は実装済み。続けて、★応用機能を段階 9 以降で作る。
未確定の点は `docs/questions.md` の確認事項（Q-xx）を参照。

## 設計書

| 種類     | 設計書                        | 内容                           |
| -------- | ----------------------------- | ------------------------------ |
| 画面     | `docs/screen-list.md`         | 画面一覧                       |
|          | `docs/screen-transitions.md`  | 画面遷移図                     |
|          | `docs/screen-items.md`        | 画面項目定義                   |
|          | `docs/screen-input-checks.md` | 入力チェックとエラーメッセージ |
| データ   | `docs/er-diagram.md`          | ER図                           |
|          | `docs/table-definitions.md`   | テーブル定義書                 |
|          | `docs/crud-matrix.md`         | CRUD表                         |
| 処理     | `docs/permission-matrix.md`   | 権限マトリクス                 |
|          | `docs/routing.md`             | ルーティング                   |
|          | `docs/api-spec.md`            | 公開API 仕様                   |
|          | `docs/state-transitions.md`   | 状態遷移図（基本機能では作らない） |
|          | `docs/sequence-diagrams.md`   | シーケンス図                   |
|          | `docs/error-handling.md`      | 例外方針                       |
| そのほか | `docs/questions.md`           | 確認事項                       |
|          | `docs/validation-rules.md`    | バリデーションルール           |
|          | `docs/acceptance-criteria.md` | 受け入れ条件                   |
|          | `docs/test-viewpoints.md`     | テスト観点                     |

## 機能一覧

### 書籍

| ID   | 機能名         | 概要                                                                                                     | 認証 / 認可          | 画面・ルート                                   | 根拠                                                    |
| ---- | -------------- | -------------------------------------------------------------------------------------------------------- | -------------------- | ---------------------------------------------- | ------------------------------------------------------- |
| F-01 | 書籍一覧表示   | 全書籍を最新順に 10 件/ページで表示。各書籍に画像・タイトル・著者・ジャンル・平均評価（★と数値）を表示 | 不要                 | PG01 `GET /`・`GET /books`（`books.index`）    | 要件 PG01、`books/index.blade.php`                      |
| F-02 | 書籍詳細表示   | 書籍情報・ジャンル・レビュー一覧を表示。お気に入り・いいね・レビュー投稿の操作を置く                     | 不要                 | PG02 `GET /books/{book}`（`books.show`）       | 要件 PG02、`books/show.blade.php`                       |
| F-03 | 書籍登録       | タイトル・著者・ISBN・出版日・説明・画像URL・ジャンル（チェックボックス）を入力して登録                  | 必要                 | PG03 `GET /books/create`、`books.store`        | 要件 PG03・DR01・DR03、`books/create`・`_form`          |
| F-04 | 書籍編集       | 登録内容とジャンルの紐付けを更新                                                                         | 必要＋作成者のみ     | PG04 `GET /books/{book}/edit`、`books.update`  | 要件 PG04、`books/edit`・`_form`、`show` の `@can('update')` |
| F-05 | 書籍削除       | 確認ダイアログを出してから削除                                                                           | 必要＋作成者のみ     | `books.destroy`（DELETE）                      | `books/show.blade.php` の `@can('delete')`・削除フォーム |

### ジャンル

| ID   | 機能名         | 概要                                                   | 認証 / 認可 | 画面・ルート                                       | 根拠                                                 |
| ---- | -------------- | ------------------------------------------------------ | ----------- | -------------------------------------------------- | ---------------------------------------------------- |
| F-06 | ジャンル一覧   | ジャンル名と各ジャンルの書籍数を表示。編集・削除の操作を置く | 必要        | PG05 `GET /genres`（`genres.index`）               | 要件 PG05、`genres/index.blade.php`                  |
| F-07 | ジャンル詳細   | 選んだジャンルの書籍を 10 件/ページで表示              | 必要        | PG06 `GET /genres/{genre}`（`genres.show`）        | 要件 PG06、`genres/show.blade.php`                   |
| F-08 | ジャンル登録   | ジャンル名（ユニーク）を登録                           | 必要        | PG07 `GET /genres/create`、`genres.store`          | 要件 PG07・DR02、`genres/create.blade.php`           |
| F-09 | ジャンル編集   | ジャンル名を更新                                       | 必要        | PG08 `GET /genres/{genre}/edit`、`genres.update`   | 要件 PG08、`genres/edit.blade.php`                   |
| F-10 | ジャンル削除   | 確認ダイアログを出してから削除。失敗時はエラーメッセージを表示 | 必要        | `genres.destroy`（DELETE）                         | `genres/index.blade.php` の削除フォーム・`session('error')` |

### レビュー・いいね・お気に入り

| ID   | 機能名                   | 概要                                                           | 認証 / 認可      | 画面・ルート                                          | 根拠                                                  |
| ---- | ------------------------ | -------------------------------------------------------------- | ---------------- | ----------------------------------------------------- | ----------------------------------------------------- |
| F-11 | レビュー投稿             | 書籍詳細で評価（1〜5）とコメントを投稿                         | 必要             | `reviews.store`（POST、`$book` を受け取る）           | 要件 DR04、`books/show.blade.php` の投稿フォーム      |
| F-12 | レビュー編集             | 評価とコメントを更新                                           | 必要＋投稿者のみ | PG09 `GET /reviews/{review}/edit`、`reviews.update`   | 要件 PG09、`reviews/edit.blade.php`                   |
| F-13 | レビュー削除             | 確認ダイアログを出してから削除                                 | 必要＋投稿者のみ | `reviews.destroy`（DELETE）                           | `books/show.blade.php` の `@can('delete', $review)`   |
| F-14 | レビューいいね（切替）   | いいね／取り消しを切り替え、件数を表示。未ログインならログイン画面へ | 必要             | `reviews.like`（POST）                                | 要件 DR06、`books/show.blade.php` のいいねボタン      |
| F-15 | お気に入り（切替）       | 書籍のお気に入り登録／解除を切り替え。未ログインならログイン画面へ | 必要             | `favorites.toggle`（POST）                            | 要件 DR05、`books/show`・`favorites/index` のハートボタン |
| F-16 | お気に入り一覧           | 自分のお気に入り書籍を 10 件/ページで表示。一覧から解除も可能  | 必要             | PG10 `GET /favorites`（`favorites.index`）            | 要件 PG10、`favorites/index.blade.php`                |
| F-17 | 評価ランキング           | レビューの平均評価 TOP10 を表示（平均評価・レビュー件数つき）  | 不要             | PG11 `GET /ranking`（`ranking.index`）                | 要件 PG11、`ranking/index.blade.php`                  |

### 認証

| ID   | 機能名     | 概要                                                   | 認証 / 認可 | 画面・ルート                         | 根拠                                                  |
| ---- | ---------- | ------------------------------------------------------ | ----------- | ------------------------------------ | ----------------------------------------------------- |
| F-18 | 会員登録   | お名前・メールアドレス・パスワード（確認つき）で登録   | 不要        | PG13 `GET /register`、`register`（POST） | 要件 PG13・DR07、`auth/register.blade.php`            |
| F-19 | ログイン   | メールアドレスとパスワードでログイン（セッション認証） | 不要        | PG12 `GET /login`、`login`（POST）   | 要件 PG12、`auth/login.blade.php`                     |
| F-20 | ログアウト | ナビゲーションのメニューからログアウト                 | 必要        | `logout`（POST）                     | `layouts/navigation.blade.php`（要件の画面定義には記載なし） |

### 公開API（JSON、基本機能では認証なし）

| ID   | 機能名           | 概要                     | 認証 | エンドポイント                | 根拠                         |
| ---- | ---------------- | ------------------------ | ---- | ----------------------------- | ---------------------------- |
| F-21 | API 書籍一覧取得 | 書籍の一覧を JSON で返す | 不要 | `GET /api/v1/books`           | 要件「APIエンドポイント一覧」 |
| F-22 | API 書籍詳細取得 | 書籍 1 件を JSON で返す  | 不要 | `GET /api/v1/books/{book}`    | 同上                         |
| F-23 | API 書籍登録     | 書籍を新しく登録する     | 不要 | `POST /api/v1/books`          | 同上                         |
| F-24 | API 書籍更新     | 書籍を更新する           | 不要 | `PUT /api/v1/books/{book}`    | 同上                         |
| F-25 | API 書籍削除     | 書籍を削除する           | 不要 | `DELETE /api/v1/books/{book}` | 同上                         |

### ★応用機能（段階 9 以降で作る）

| ID   | 機能名                     | 概要                                                                                   | 認証 / 認可      | 画面・ルート                                                   | 根拠                                                               |
| ---- | -------------------------- | -------------------------------------------------------------------------------------- | ---------------- | -------------------------------------------------------------- | ------------------------------------------------------------------ |
| F-26 | 書籍の検索・絞り込み・並び替え | 書籍一覧に、キーワード検索・ジャンルでの絞り込み・並び替えを足し、条件をページ送りに引き継ぐ | 不要             | PG01（`books.index`）                                          | 要件 ★PG01、`books/index.blade.php`                                |
| F-27 | ISBN 検索                  | ISBN から Google Books API で書籍情報を取得し、書籍の登録・編集フォームに入れる        | 必要             | PG03・PG04、`GET /books/isbn/{isbn}`                           | 要件 ★PG03、「外部API連携」、`books/create`・`books/edit`          |
| F-28 | 読書計画一覧               | 自分の読書計画を一覧表示し、状態で絞り込む                                             | 必要             | PG15 `GET /reading-plans`（`reading-plans.index`）             | 要件 ★PG15・★DR09、`reading-plans/index.blade.php`                 |
| F-29 | 読書計画作成               | 書籍と期日を指定して、読書計画を登録                                                   | 必要             | PG16 `GET /reading-plans/create`、`reading-plans.store`        | 要件 ★PG16、`reading-plans/create.blade.php`                       |
| F-30 | 読書計画編集               | 期日を変更                                                                             | 必要＋所有者のみ | PG17 `GET /reading-plans/{plan}/edit`、`reading-plans.update`  | 要件 ★PG17、`reading-plans/edit.blade.php`                         |
| F-31 | 読書計画削除               | 確認ダイアログを出してから削除                                                         | 必要＋所有者のみ | `reading-plans.destroy`（DELETE）                              | `reading-plans/index.blade.php` の削除フォーム                     |
| F-32 | 読書計画の読了             | 読書計画を読了にする                                                                   | 必要＋所有者のみ | `reading-plans.complete`（POST）                               | `reading-plans/index.blade.php` の「読了する」                     |
| F-33 | 読書計画の日次バッチ       | 毎日の定刻に、期日を経過した読書計画の状態を自動で変え、条件を満たす計画にリマインダー通知を出す | －（バッチ）     | Console Command + Schedule                                     | 要件「Schedule + Console Command」「Notification facade」、要件シート（日次バッチ） |
| F-34 | 通知一覧                   | 自分の通知を一覧表示し、ナビゲーションに未読件数を出す                                 | 必要             | PG18 `GET /notifications`（`notifications.index`）             | 要件 ★PG18・★DR10、`notifications/index`・`layouts/navigation`     |
| F-35 | 通知の既読化               | 通知を 1 件ずつ既読にする                                                              | 必要＋本人のみ   | `notifications.read`（POST）                                   | 要件 ★PG18、`notifications/index.blade.php`                        |
| F-36 | マイ読書レポート           | 自分の読書統計（総レビュー数・読了冊数・平均評価・評価分布・高評価書籍・ジャンル別の傾向）を表示 | 必要             | PG14 `GET /reports`（`reports.index`）                         | 要件 ★PG14、「Collectionメソッド活用」、`reports/index.blade.php`  |
| F-37 | API トークン発行           | 公開API の書き込みに使うトークンを発行する                                             | 段階 15 で決める | 段階 15 で決める                                               | `docs/questions.md` Q-35（要件に発行手段の記載は無い）             |
| F-38 | 公開API の認証・認可       | F-23〜F-25 を Sanctum のトークン認証にし、更新・削除は BookPolicy で所有者だけに許す   | 必要＋所有者のみ（登録は認証のみ） | `POST`・`PUT`・`DELETE /api/v1/books`                          | 要件「APIエンドポイント一覧」の認証（応用）列、「Sanctum APIトークン認証」 |

## 実装の順序

依存の向きに沿って、段階ごとに動く画面を作っていく。各段階は、機能ごとにブランチを切って main にマージする。段階ごとに、テストを書いて `docs/acceptance-criteria.md` の該当する行を試してから次へ進む。

- 認証が先。ジャンル・お気に入り・書籍登録は、ログインが前提になる。
- ジャンルは書籍より先。書籍登録フォームのジャンル（チェックボックス）に必要になる。
- 書籍詳細（F-02）のビューは、レビュー・いいね・お気に入り・書籍の編集・削除のルート名を `route()` で呼ぶ。ルート名が無いと表示時にエラーになるため、全ルートを段階 0 で先に定義する。
- 公開API は機能の中で最後。書籍のモデルと入力チェックを使い回す。
- README は、実装がすべて終わってからまとめて書く（Q-36）。

| 段階 | 作るもの                                                                                     | 機能          |
| ---- | -------------------------------------------------------------------------------------------- | ------------- |
| 0-1  | Fortify の導入、`RouteServiceProvider::HOME` を `/` に変更（Q-08）、2 段階認証のマイグレーションを削除（Q-38）、`locale` を `ja` に変更（Q-42）、`timezone` を `Asia/Tokyo` に変更（Q-51）、`lang/` の追加（Q-42・Q-53） | －            |
| 0-2  | 全ルートの定義（`docs/routing.md`）。本体は各段階で埋める。`/` を差し替えるのと同時に `welcome.blade.php` を削除（Q-01） | －            |
| 0-3  | マイグレーション、モデルとリレーション、ファクトリー、シーダー 6 本                          | －            |
| 1    | 認証（会員登録・ログイン・ログアウト）                                                       | F-18〜F-20    |
| 2    | ジャンル（一覧・詳細・登録・編集・削除。書籍が紐付いていると削除させない）                   | F-06〜F-10    |
| 3    | 書籍（一覧・詳細・登録・編集・削除）                                                         | F-01〜F-05    |
| 4    | レビュー（投稿・編集・削除）                                                                 | F-11〜F-13    |
| 5    | いいね・お気に入り（いいね切替・お気に入り切替・お気に入り一覧）                             | F-14〜F-16    |
| 6    | 評価ランキング                                                                               | F-17          |
| 7    | 公開API（書籍の一覧・詳細・登録・更新・削除）                                                | F-21〜F-25    |
| 8    | README（要件「README.md記載内容」の項目と、開発ルールの例外（Q-36））                        | －            |
| 9-1  | `reading_plans`・`notifications` のマイグレーション、`books` の `published_date`・`isbn`（Q-80）の nullable 化 | －            |
| 9-2  | Advanced 版 Blade の反映、応用機能のルートの仮定義、画面の表示に必要な最小限の対応、`welcome.blade.php` の削除（Q-01） | －            |
| 9-3  | Google Books API キーの環境設定                                                              | －            |
| 9-4  | Sanctum の設定の確認                                                                         | －            |
| 9-5  | 既存コードの型宣言・PHPDoc・Collection 化のリファクタ                                        | －            |
| 9-6  | FormRequest の応用差分対応（書籍の必須項目の任意化）                                         | －            |
| 10   | 書籍一覧の検索・ジャンル絞り込み・並び替え                                                   | F-26          |
| 11   | ISBN 検索（Google Books API を `Http` で呼び、テストは `Http::fake()`）                      | F-27          |
| 12   | 読書計画（一覧・作成・編集・削除・読了。状態は Enum と cast）                                | F-28〜F-32    |
| 13   | 日次バッチ（期日を経過した読書計画の状態の変更と、リマインダー通知。Command と Schedule）、通知一覧・既読化 | F-33〜F-35    |
| 14   | マイ読書レポート（集計は Collection）                                                        | F-36          |
| 15   | 公開API の認証・認可（トークン発行、`auth:sanctum`、`BookPolicy`、登録者 ID の入力チェックの見直し） | F-37・F-38    |
| 16   | 仕上げ（README の更新、カバレッジ 80% 以上の確認、環境構築手順の通し確認）                   | －            |

- 段階 0-1〜0-3 は、それぞれ別のイシュー・ブランチ・PR にする。0-1 と 0-2 は互いに依存しないので、順番は入れ替えてよい。0-3 は 0-1 の後。
- 段階 0-2 の仮実装：コントローラーは、本体を作る段階まで `abort(501)` を返す。`Book` などのモデルは段階 0-3 までできないため、仮のメソッドは引数なしにし、本体を作る段階で型を付ける。`books.index` だけは、ログイン後の移動先を表示するため、空の一覧を渡して `books/index` を返す（段階 3 までは、書籍があっても「書籍が登録されていません。」と出る）。
- 段階 0-2 のテスト：`/` の差し替えで `tests/Feature/ExampleTest.php` が前提を失うため削除する。サンプルの `tests/Unit/ExampleTest.php` も一緒に削除する。単体テストは後の段階で追加するため、最初の単体テストができるまでは、`tests/Unit/` を残すために `.gitkeep` を置く（`phpunit.xml` の `Unit` スイートが指している）。その後、モデルのリレーションのテストを `tests/Feature/Models/` から `tests/Unit/Models/` に移し（要件「テスト要件」の単体テスト）、`.gitkeep` は削除した。`tests/TestCase.php` で `withoutVite()` を呼び、画面を返すテストをビルドなしで動くようにする。
- 段階 0-2 の仮実装のテスト：`RouteDefinitionTest` には、仮実装（`abort(501)`）を前提にしたテスト（`test_authenticated_user_gets_not_implemented_for_stub_routes`・`test_public_routes_do_not_require_login`）があった。ルートの本体を作った段階で、そのルートをこれらの対象から外し、本来のテスト（正しい画面が出るなど）をその段階の `*ControllerTest` に書く。外し忘れると、501 を期待するテストが失敗する。段階 5 で、ログインが必要なルートの仮実装がなくなったため、`test_authenticated_user_gets_not_implemented_for_stub_routes` と、その対象を決める `stubRouteProvider` は削除した。残る `test_public_routes_do_not_require_login` は、段階 6 で `/ranking` を対象から外し、対象は `/api/v1/books` だけになった。段階 7 でそのルートが 501 でなくなり、対象が空になったため、このテストも削除した。公開API のログイン不要は、`tests/Feature/Api/V1/BookControllerTest.php` の `*_needs_neither_login_nor_csrf_token` で確かめる。
- 段階 0-3 のシーダー：要件に無い内容（レビューのコメントと配分、書籍の説明文、お気に入り、いいね）は、案を作って確認を取ってから実装する。
- AC-2-4 は書籍登録画面が前提のため、段階 1 の PR には「段階 3 で判定」と書き、段階 3 の PR で判定する。
- 段階 3 の書籍詳細は、レビュー・いいね・お気に入りの本体ができるまで、投稿・切替の操作は動かない（ルート名は 0-2 で揃っている）。段階 5 の後に詳細画面を通しで確かめる。
- 段階 3 のテスト：段階 0-2 の `tests/Feature/RouteDefinitionTest.php` にある `test_top_page_shows_empty_book_list` は、`tests/Feature/BookControllerTest.php` に移す（TP-3-6）。書籍が無いときの一覧のテストとして残し、書籍がある場合のテスト（TP-3-5 など）は同じファイルに足す。
- 段階 9 以降（★応用機能）も、段階ごとにイシュー・ブランチ・PR を分け、該当する設計書とテストを同じ作業で直す。設計書に決めが無い点は、その段階の着手時に確認して `docs/questions.md` に足す。
- 段階 9-1 は 9-2 より先にする。反映後のナビゲーションが、ログイン中の全画面で通知テーブルを読むため。
- 段階 9-6 は 9-1 の後。11 は 9-2・9-3・9-6 の後。12 は 9-2 の後。13・14 は 12 の後（14 は、読了冊数を読書計画から数える想定）。15 は 9-4・9-6 の後。16 は最後。
- 段階 10・11・12・15 は互いに依存しないので、順番を入れ替えてよい。
- `DB::transaction()`（要件「★Database Transaction」）のための段階は作らず、書き込みが複数の SQL になる段階で使う。

## 今回作らないもの

### 要件にもビューにも無いもの

| 項目 | 根拠 |
| ---- | ---- |
| パスワードリセット（再設定メールの送信・再設定画面） | `docs/questions.md` Q-29（要件の画面定義に無く、提供ビューも無い。テーブルは残す） |
| 会員登録後のメールアドレス確認 | `docs/questions.md` Q-30（要件の画面定義に無く、提供ビューも無い） |
| プロフィール編集（名前・メールアドレス・パスワードの変更）と退会 | `docs/questions.md` Q-31（要件の画面定義に無く、提供ビューも無い） |
| 2 段階認証 | `docs/questions.md` Q-38（要件シートに、基本・応用のどちらでも不要と記載） |
| ログイン状態の保持（Remember me） | `docs/questions.md` Q-32（提供ビューにチェックボックスが無い） |
| 管理者などのロール・権限管理 | `docs/questions.md` Q-33（要件シートに無い） |
| 書籍画像ファイルのアップロード | `docs/questions.md` Q-34（提供ビューにファイルの選択欄が無い） |
