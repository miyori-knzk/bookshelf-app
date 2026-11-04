# CRUD表

機能（`docs/features.md` の F-xx）ごとに、どのテーブルをどう操作するかをまとめたもの。範囲は基本機能のみ。
テーブルは `docs/table-definitions.md`、つながりは `docs/er-diagram.md` を参照。

## 表の見かた

| 記号          | 意味                                                                                         |
| ------------- | -------------------------------------------------------------------------------------------- |
| C             | 行を追加する                                                                                 |
| R             | 行を読む                                                                                     |
| U             | 行を更新する                                                                                 |
| D             | 行を削除する                                                                                 |
| D（cascade）  | アプリは削除せず、親の行の削除に続いて DB が外部キーの cascade で削除する（Q-05・Q-12）      |
| －            | そのテーブルを操作しない                                                                     |

- 1 つの機能で複数の操作をするときは、記号を「・」でつなぐ（例：C・R）。

- 入力チェックの `exists`・`unique` でテーブルを読む場合も R に含める。
- ログイン中は、どの画面でもログイン中のユーザーを `users` から読む（セッション認証の動き）。これは表に含めない。表の `users` の R は、機能として `users` を読むもの（投稿者名の表示など）だけ。
- 作成者・投稿者だけに許す操作（Policy）は、対象の行の `user_id` を見る。その行を読む R に含める。

## CRUD表

| ID   | 機能名                 | users | books | genres | book_genre | reviews | favorites | review_likes | 根拠 |
| ---- | ---------------------- | ----- | ----- | ------ | ---------- | ------- | --------- | ------------ | ---- |
| F-01 | 書籍一覧表示           | －     | R     | R      | R          | R       | －         | －            | `books/index`（ジャンル名、`reviews_avg_rating`）、Q-13 |
| F-02 | 書籍詳細表示           | R     | R     | R      | R          | R       | R         | R            | `books/show`（ジャンル名、`$review->user->name`、`favoriteBooks`・`likedReviews` での判定、`likedByUsers` の件数）、Q-14 |
| F-03 | 書籍登録               | －     | C・R  | R      | C          | －       | －         | －            | `books/create`・`_form`（ジャンルのチェックボックス）、`docs/validation-rules.md`（`unique:books,isbn`・`exists:genres,id`）、Q-05 |
| F-04 | 書籍編集               | －     | R・U  | R      | R・C・D    | －       | －         | －            | `books/edit`・`_form`（登録内容とジャンルの選択状態）、`docs/validation-rules.md`（ISBN の一意性は自分を除く）、Q-05 |
| F-05 | 書籍削除               | －     | R・D  | －      | D（cascade） | D（cascade） | D（cascade） | D（cascade） | `books/show` の削除フォーム、Q-05・Q-12 |
| F-06 | ジャンル一覧           | －     | －     | R      | R          | －       | －         | －            | `genres/index`（`books_count`）、Q-19 |
| F-07 | ジャンル詳細           | －     | R     | R      | R          | －       | －         | －            | `genres/show`（そのジャンルの書籍と、各書籍のジャンル名）、Q-19 |
| F-08 | ジャンル登録           | －     | －     | C・R   | －          | －       | －         | －            | `genres/create`、`docs/validation-rules.md`（`unique:genres,name`）、Q-48 |
| F-09 | ジャンル編集           | －     | －     | R・U   | －          | －       | －         | －            | `genres/edit`、`docs/validation-rules.md`（一意性は自分を除く）、Q-48 |
| F-10 | ジャンル削除           | －     | －     | R・D   | R          | －       | －         | －            | `genres/index` の削除フォームと `session('error')`、Q-17（書籍が紐付いていたら削除しない） |
| F-11 | レビュー投稿           | －     | R     | －      | －          | C       | －         | －            | `books/show` の投稿フォーム（`reviews.store` に書籍を渡す） |
| F-12 | レビュー編集           | －     | R     | －      | －          | R・U    | －         | －            | `reviews/edit`（`$review->book->title`、評価・コメントの初期値） |
| F-13 | レビュー削除           | －     | －     | －      | －          | R・D    | －         | D（cascade） | `books/show` の削除フォーム、Q-12 |
| F-14 | レビューいいね（切替） | －     | －     | －      | －          | R       | －         | R・C・D      | `books/show` のいいねボタン（`reviews.like`） |
| F-15 | お気に入り（切替）     | －     | R     | －      | －          | －       | R・C・D   | －            | `books/show`・`favorites/index` のハートボタン（`favorites.toggle`） |
| F-16 | お気に入り一覧         | －     | R     | －      | －          | －       | R         | －            | `favorites/index`（画像・タイトル・著者・ISBN）、Q-23 |
| F-17 | 評価ランキング         | －     | R     | －      | －          | R       | －         | －            | `ranking/index`（`reviews_avg_rating`・`reviews_count`）、Q-24 |
| F-18 | 会員登録               | C・R  | －     | －      | －          | －       | －         | －            | `auth/register`、`docs/validation-rules.md`（`unique:users`） |
| F-19 | ログイン               | R     | －     | －      | －          | －       | －         | －            | `auth/login` |
| F-20 | ログアウト             | －     | －     | －      | －          | －       | －         | －            | `layouts/navigation`（下の「埋まらない行」を参照） |
| F-21 | API 書籍一覧取得       | R     | R     | R      | R          | R       | －         | －            | Q-58-1（`user`・`genres`・平均評価・レビュー件数を含める）、Q-43（キーワード・ジャンルIDでの絞り込み） |
| F-22 | API 書籍詳細取得       | R     | R     | R      | R          | R       | －         | －            | Q-58-2（`user`・`genres`・`reviews` を含める） |
| F-23 | API 書籍登録           | R     | C・R  | R      | C          | －       | －         | －            | Q-26（`exists:users,id`）、Q-28（Web と同じ入力チェック）、Q-57（レスポンス）、Q-05 |
| F-24 | API 書籍更新           | R     | R・U  | R      | R・C・D    | －       | －         | －            | Q-28・Q-41（作成者は変えない）、Q-57（レスポンス）、Q-05 |
| F-25 | API 書籍削除           | －     | R・D  | －      | D（cascade） | D（cascade） | D（cascade） | D（cascade） | Q-12（公開API も同じ）、Q-05 |

## 行ごとの補足

| ID           | 補足                                                                                                                                  |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------- |
| F-02         | `favorites`・`review_likes` は、ログイン中だけ読む（お気に入り済み・いいね済みの判定）。いいねの件数は、ログインしていなくても読む       |
| F-03・F-23   | `books` に追加したあと `book_genre` に追加する。この 2 つは `DB::transaction()` でまとめる（Q-05）                                      |
| F-04・F-24   | `books` を更新したあと、`book_genre` を `sync` で入れ替える（外したジャンルの行を削除し、新しく選んだジャンルの行を追加する）。この 2 つは `DB::transaction()` でまとめる（Q-05） |
| F-05・F-25   | アプリが削除するのは `books` の 1 行だけ。`review_likes` は、cascade で削除される `reviews` に続いて cascade で削除される（Q-12）           |
| F-10         | `book_genre` に行があるジャンルは削除しない（Q-17）。外部キーでも削除できない                                                         |
| F-14・F-15   | 今の状態を読み、無ければ追加し、あれば削除する（`toggle`）                                                                            |
| F-15         | お気に入り一覧（SC-10）からの解除も、この機能                                                                                         |
| F-17         | レビューが 1 件以上ある書籍だけを対象にする（Q-24）                                                                                   |
| F-19         | ログイン回数の制限はキャッシュ（`file`）を使うため、DB のテーブルは操作しない                                                         |

## 埋まらない行・列

| 対象 | 内容 |
| ---- | ---- |
| F-20 ログアウト（行） | どのテーブルも操作しない。セッションは `file` に保存し、ログイン状態の保持（`remember_token`）も使わない（Q-32）ため。ログアウトで `remember_token` が書き換わるのは、値が入っているときだけ |
| `users` の U（列） | `users` を更新する機能が無い（プロフィール編集を作らない、Q-31） |
| Laravel 標準のテーブル（列） | `password_reset_tokens`・`failed_jobs`・`personal_access_tokens` は、どの機能も操作しないため列を設けていない（`docs/table-definitions.md`「Laravel 標準のテーブル」） |

## シーダー

シーダー（要件「シーディング」）は機能ではないため、表に含めない。どのシーダーも、対象のテーブルに C を行う（`firstOrCreate` の場合は R も行う）。
