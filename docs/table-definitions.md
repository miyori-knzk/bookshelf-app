# テーブル定義書

テーブルごとの列・型・制約をまとめたもの。範囲は基本機能と、★応用機能の段階 9-1 で作ったテーブル・列（`docs/features.md` 参照）。
テーブルのつながりは `docs/er-diagram.md`、機能ごとのテーブル操作は `docs/crud-matrix.md` を参照。

## 共通

| 項目           | 内容                                                                                       | 根拠                                  |
| -------------- | ------------------------------------------------------------------------------------------ | ------------------------------------- |
| DB             | MySQL 8.4                                                                                  | 要件「技術スタック」                  |
| 文字コード     | `utf8mb4`                                                                                  | `config/database.php`                 |
| 照合順序       | `utf8mb4_unicode_ci`（Laravel の初期値のまま）。大文字と小文字・全角と半角・ひらがなとカタカナ・濁点と半濁点の違いは同じ文字とみなす | `config/database.php`、Q-48 |
| strict モード  | 有効。列に入りきらない値は切り詰めず、DB エラーになる。そのため入力チェックの上限は列の大きさ以下にする | `config/database.php`、`docs/validation-rules.md` |
| 削除           | 物理削除（`SoftDeletes` は使わない）。論理削除では外部キーの cascade が働かないため          | Q-12                                  |
| 平均評価・件数 | 列に持たず、表示のたびに `withAvg`・`withCount` で集計する                                 | `docs/screen-items.md`                |
| 中間テーブル   | すべて `id`（主キー）と `created_at`・`updated_at` を持つ。2 つの外部キーの組には、複合ユニーク制約を付ける | お客さんの指摘 |

### 表の見かた

| 列         | 内容                                                                                   |
| ---------- | -------------------------------------------------------------------------------------- |
| 型         | MySQL の型。カッコ内はマイグレーションでの書き方                                       |
| NULL       | ○ は NULL を許す                                                                       |
| キー       | PK：主キー、FK：外部キー、UK：ユニーク制約                                             |
| 入力の上限 | 入力チェックでの上限（`docs/validation-rules.md`）。列の大きさより小さいか同じにする   |

`created_at`・`updated_at` は、どのテーブルも `timestamps()` で作る（`TIMESTAMP`、NULL 可）。

## アプリのテーブル

### users（ユーザー）

要件 DR07。Laravel 標準のマイグレーション（`2014_10_12_000000_create_users_table.php`）をそのまま使う。

| No. | 列名                | 論理名             | 型                                         | NULL | キー | 入力の上限 | 備考                                                    |
| --- | ------------------- | ------------------ | ------------------------------------------ | ---- | ---- | ---------- | ------------------------------------------------------- |
| 1   | `id`                | ID                 | `BIGINT UNSIGNED`（`id()`）                |      | PK   | －         | 自動採番                                                |
| 2   | `name`              | お名前             | `VARCHAR(255)`（`string()`）               |      |      | 255 文字   |                                                         |
| 3   | `email`             | メールアドレス     | `VARCHAR(255)`（`string()`）               |      | UK   | 255 文字   | 大文字と小文字を区別せずに重複を判定する（照合順序）    |
| 4   | `email_verified_at` | メール確認日時     | `TIMESTAMP`（`timestamp()`）               | ○    |      | －         | 今回は使わない（Q-30）                                  |
| 5   | `password`          | パスワード         | `VARCHAR(255)`（`string()`）               |      |      | －         | ハッシュ化して保存する                                  |
| 6   | `remember_token`    | ログイン保持トークン | `VARCHAR(100)`（`rememberToken()`）      | ○    |      | －         | 今回は使わない（Q-32。ログイン画面にチェックボックスが無い） |
| 7   | `created_at`        | 登録日時           | `TIMESTAMP`                                | ○    |      | －         |                                                         |
| 8   | `updated_at`        | 更新日時           | `TIMESTAMP`                                | ○    |      | －         |                                                         |

- Fortify の 2 段階認証の列（`two_factor_secret` など）は足さない。Fortify が足すマイグレーションは削除する（Q-38）。

### books（書籍）

要件 DR01。

| No. | 列名             | 論理名   | 型                                       | NULL | キー | 入力の上限        | 備考                                                    |
| --- | ---------------- | -------- | ---------------------------------------- | ---- | ---- | ----------------- | ------------------------------------------------------- |
| 1   | `id`             | ID       | `BIGINT UNSIGNED`（`id()`）              |      | PK   | －                | 自動採番                                                |
| 2   | `user_id`        | 登録者ID | `BIGINT UNSIGNED`（`foreignId()`）       |      | FK   | －                | `users.id` を参照。ユーザーは削除させない（Q-31）。公開API では、リクエストの `user_id` を入れる（Q-26） |
| 3   | `title`          | タイトル | `VARCHAR(255)`（`string()`）             |      |      | 255 文字          |                                                         |
| 4   | `author`         | 著者     | `VARCHAR(255)`（`string()`）             |      |      | 255 文字          |                                                         |
| 5   | `isbn`           | ISBN     | `VARCHAR(13)`（`string('isbn', 13)`）    | ○    | UK   | 数字 13 桁        | ハイフン無しの ISBN-13。★応用機能で NULL 可にした（Q-80）。NULL どうしは重複にならない。入力チェックは段階 9-6 まで必須のまま |
| 6   | `published_date` | 出版日   | `DATE`（`date()`）                       | ○    |      | －                | ★応用機能で NULL 可にした（★DR01）。入力チェックは段階 9-6 まで必須のまま |
| 7   | `description`    | 説明     | `TEXT`（`text()`）                       | ○    |      | 16,000 文字       | `TEXT` には、どんな文字でも 16,000 文字ほどまで入る。お客さんの回答は「上限なし」だったが、この上限でよいことを確認した（Q-64） |
| 8   | `image_url`      | 画像URL  | `VARCHAR(255)`（`string()`）             | ○    |      | 255 文字          |                                                         |
| 9   | `created_at`     | 登録日時 | `TIMESTAMP`                              | ○    |      | －                | 書籍一覧などの「最新順」に使う（Q-13）                  |
| 10  | `updated_at`     | 更新日時 | `TIMESTAMP`                              | ○    |      | －                |                                                         |

### genres（ジャンル）

要件 DR02。

| No. | 列名         | 論理名     | 型                            | NULL | キー | 入力の上限 | 備考                                                         |
| --- | ------------ | ---------- | ----------------------------- | ---- | ---- | ---------- | ------------------------------------------------------------ |
| 1   | `id`         | ID         | `BIGINT UNSIGNED`（`id()`）   |      | PK   | －         | 自動採番                                                     |
| 2   | `name`       | ジャンル名 | `VARCHAR(255)`（`string()`）  |      | UK   | 255 文字   | 列も入力の上限も 255 文字（Q-59）。重複の判定は照合順序による（Q-48） |
| 3   | `created_at` | 登録日時   | `TIMESTAMP`                   | ○    |      | －         |                                                              |
| 4   | `updated_at` | 更新日時   | `TIMESTAMP`                   | ○    |      | －         |                                                              |

### book_genre（書籍×ジャンル）

要件 DR03。中間テーブル。テーブル名は Q-49 の想定。

| No. | 列名         | 論理名     | 型                                   | NULL | キー   | 備考                                                    |
| --- | ------------ | ---------- | ------------------------------------ | ---- | ------ | ------------------------------------------------------- |
| 1   | `id`         | ID         | `BIGINT UNSIGNED`（`id()`）          |      | PK     | 自動採番                                                |
| 2   | `book_id`    | 書籍ID     | `BIGINT UNSIGNED`（`foreignId()`）   |      | FK・UK | `books.id` を参照。書籍を削除すると一緒に削除（cascade、Q-12）。`genre_id` との組でユニーク |
| 3   | `genre_id`   | ジャンルID | `BIGINT UNSIGNED`（`foreignId()`）   |      | FK・UK | `genres.id` を参照。紐付いているジャンルは削除させない（Q-17）。`book_id` との組でユニーク |
| 4   | `created_at` | 紐付け日時 | `TIMESTAMP`                          | ○    |        | `withTimestamps()` で入れる                             |
| 5   | `updated_at` | 更新日時   | `TIMESTAMP`                          | ○    |        | 同上                                                    |

- 主キーは `id`。`(book_id, genre_id)` にユニーク制約を付けて、同じ書籍に同じジャンルを 2 回は付けられない。

### reviews（レビュー）

要件 DR04。

| No. | 列名         | 論理名   | 型                                           | NULL | キー | 入力の上限 | 備考                                                    |
| --- | ------------ | -------- | -------------------------------------------- | ---- | ---- | ---------- | ------------------------------------------------------- |
| 1   | `id`         | ID       | `BIGINT UNSIGNED`（`id()`）                  |      | PK   | －         | 自動採番                                                |
| 2   | `user_id`    | 投稿者ID | `BIGINT UNSIGNED`（`foreignId()`）           |      | FK   | －         | `users.id` を参照。ユーザーは削除させない（Q-31）       |
| 3   | `book_id`    | 書籍ID   | `BIGINT UNSIGNED`（`foreignId()`）           |      | FK   | －         | `books.id` を参照。書籍を削除すると一緒に削除（cascade、Q-12） |
| 4   | `rating`     | 評価     | `TINYINT UNSIGNED`（`unsignedTinyInteger()`）|      |      | 1〜5       | 範囲は入力チェックだけで守る（CHECK 制約は付けない）    |
| 5   | `comment`    | コメント | `TEXT`（`text()`）                           |      |      | 1000 文字  |                                                         |
| 6   | `created_at` | 投稿日時 | `TIMESTAMP`                                  | ○    |      | －         | 書籍詳細の投稿日と並び順に使う（Q-14）                  |
| 7   | `updated_at` | 更新日時 | `TIMESTAMP`                                  | ○    |      | －         |                                                         |

- 同じユーザーが同じ書籍に何件でもレビューを書けるため、`user_id` と `book_id` の組み合わせのユニーク制約は付けない（Q-20）。

### favorites（お気に入り）

要件 DR05。中間テーブル。テーブル名は要件「シーディング」（FavoriteSeeder）と Q-49。

| No. | 列名         | 論理名       | 型                                   | NULL | キー   | 備考                                                    |
| --- | ------------ | ------------ | ------------------------------------ | ---- | ------ | ------------------------------------------------------- |
| 1   | `id`         | ID           | `BIGINT UNSIGNED`（`id()`）          |      | PK     | 追加日時が同じときの並び順に使う（下の注）              |
| 2   | `user_id`    | ユーザーID   | `BIGINT UNSIGNED`（`foreignId()`）   |      | FK・UK | `users.id` を参照。ユーザーは削除させない（Q-31）。`book_id` との組でユニーク |
| 3   | `book_id`    | 書籍ID       | `BIGINT UNSIGNED`（`foreignId()`）   |      | FK・UK | `books.id` を参照。書籍を削除すると一緒に削除（cascade、Q-12）。`user_id` との組でユニーク |
| 4   | `created_at` | 追加日時     | `TIMESTAMP`                          | ○    |        | お気に入り一覧の並び順に使う（Q-23）。`withTimestamps()` で入れる |
| 5   | `updated_at` | 更新日時     | `TIMESTAMP`                          | ○    |        | 同上                                                    |

- 主キーは `id`。`(user_id, book_id)` にユニーク制約を付けて、同じ書籍を 2 回お気に入りにはできない。
- `id` を足す理由：お気に入り一覧は、追加日時（`created_at`）の新しい順に並べ、同じ日時のものは `id` の大きい順にする（Q-23・Q-50）。シーダーは複数のお気に入りを同じ秒に入れるため、これが無いと、ページ送りで書籍が重複したり抜けたりする。要件 DR05 は「ユーザーIDと書籍IDの組み合わせ」とだけ書いていて、列を限っていない。`book_genre`・`review_likes` も、お客さんの指摘で、中間テーブルはすべて `id` を持つ形にそろえた。

### review_likes（いいね）

要件 DR06。中間テーブル。テーブル名は要件「シーディング」（ReviewLikeSeeder）と Q-49。

| No. | 列名         | 論理名     | 型                                   | NULL | キー   | 備考                                                    |
| --- | ------------ | ---------- | ------------------------------------ | ---- | ------ | ------------------------------------------------------- |
| 1   | `id`         | ID         | `BIGINT UNSIGNED`（`id()`）          |      | PK     | 自動採番                                                |
| 2   | `user_id`    | ユーザーID | `BIGINT UNSIGNED`（`foreignId()`）   |      | FK・UK | `users.id` を参照。ユーザーは削除させない（Q-31）。`review_id` との組でユニーク |
| 3   | `review_id`  | レビューID | `BIGINT UNSIGNED`（`foreignId()`）   |      | FK・UK | `reviews.id` を参照。レビューを削除すると一緒に削除（cascade、Q-12）。`user_id` との組でユニーク |
| 4   | `created_at` | いいね日時 | `TIMESTAMP`                          | ○    |        | 画面では使わない。`withTimestamps()` で入れる           |
| 5   | `updated_at` | 更新日時   | `TIMESTAMP`                          | ○    |        | 同上                                                    |

- 主キーは `id`。`(user_id, review_id)` にユニーク制約を付けて、同じレビューに 2 回いいねはできない。

### reading_plans（読書計画）

要件 ★DR09。★応用機能。列名は、提供される Advanced 版のビューが使う名前に合わせる。

| No. | 列名           | 論理名     | 型                                   | NULL | キー | 備考                                                                 |
| --- | -------------- | ---------- | ------------------------------------ | ---- | ---- | -------------------------------------------------------------------- |
| 1   | `id`           | ID         | `BIGINT UNSIGNED`（`id()`）          |      | PK   | 自動採番                                                             |
| 2   | `user_id`      | 計画者ID   | `BIGINT UNSIGNED`（`foreignId()`）   |      | FK・UK | `users.id` を参照。ユーザーは削除させない（Q-31）。`book_id` との組でユニーク |
| 3   | `book_id`      | 書籍ID     | `BIGINT UNSIGNED`（`foreignId()`）   |      | FK・UK | `books.id` を参照。読書計画がある書籍は削除させない（Q-82）。`user_id` との組でユニーク |
| 4   | `target_date`  | 期日       | `DATE`（`date()`）                   |      |      | モデルで `date` の cast が必要（ビューが `format()` を呼ぶ）         |
| 5   | `status`       | 状態       | `ENUM`（`enum()`）                   |      |      | `in_progress`（進行中）・`completed`（読了）・`expired`（期限切れ）。初期値は `in_progress`（Q-83） |
| 6   | `completed_at` | 完了日     | `TIMESTAMP`（`timestamp()`）         | ○    |      | 画面には日付だけを出す。モデルで `datetime` の cast が必要           |
| 7   | `created_at`   | 登録日時   | `TIMESTAMP`                          | ○    |      |                                                                      |
| 8   | `updated_at`   | 更新日時   | `TIMESTAMP`                          | ○    |      |                                                                      |

- 状態の画面での名前は、段階 12 で決める。
- `(user_id, book_id)` にユニーク制約を付けて、同じ利用者が同じ書籍の計画を 2 件持てないようにする（状態を問わない、Q-84）。画面のエラーメッセージは、段階 12 の入力チェックで出す。

## Laravel 標準のテーブル

要件 DR08・★DR10。Laravel 標準のマイグレーションをそのまま使う。どれも基本機能では読み書きしない（`docs/crud-matrix.md` に列を設けていない）。

| テーブル                 | 列                                                                                         | 基本機能での扱い                                                       |
| ------------------------ | ------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------- |
| `password_reset_tokens`  | `email`（PK）・`token`・`created_at`                                                       | パスワードリセットを作らないため使わない（Q-29）                       |
| `failed_jobs`            | `id`（PK）・`uuid`（UK）・`connection`・`queue`・`payload`・`exception`・`failed_at`        | 基本機能にはキューで動かす処理が無いため、行は入らない |
| `personal_access_tokens` | `id`（PK）・`tokenable_type`・`tokenable_id`・`name`・`token`（UK）・`abilities`・`last_used_at`・`expires_at`・`created_at`・`updated_at` | 基本機能の公開API は認証なしのため使わない（Q-35）。★応用機能の Sanctum で使う |
| `notifications`          | `id`（PK、UUID）・`type`・`notifiable_type`・`notifiable_id`・`data`・`read_at`・`created_at`・`updated_at` | ★応用機能の通知（★DR10）で使う（段階 13）。受信者は `notifiable_type`・`notifiable_id` で持ち、外部キーは無い |

- セッション・キャッシュは `file` に保存するため、`sessions`・`cache` テーブルは作らない。
