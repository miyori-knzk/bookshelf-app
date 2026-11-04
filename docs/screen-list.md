# 画面一覧

`resources/views/` 配下のビュー（以下「ビュー」）をもとに、画面を一覧にしたもの。
画面 ID は SC-01 からの連番で、要件の画面 ID（PG01〜PG13）と同じ順に並べている。
範囲は基本機能のみ（`docs/features.md` 参照）。未確定の点は `docs/questions.md` の確認事項（Q-xx）を参照。

関連する設計書：

- 画面遷移図：`docs/screen-transitions.md`
- 画面項目定義：`docs/screen-items.md`
- 入力チェックとエラーメッセージ：`docs/screen-input-checks.md`

## 画面一覧

| 画面ID | 画面名         | 要件   | URL（GET）               | ルート名          | ビュー                    | レイアウト | 認証 / 認可      | 主な表示内容・操作                                                                                     | 機能        |
| ------ | -------------- | ------ | ------------------------ | ----------------- | ------------------------- | ---------- | ---------------- | ------------------------------------------------------------------------------------------------------ | ----------- |
| SC-01  | 書籍一覧       | PG01   | `/`（Q-01）              | `books.index`     | `books/index.blade.php`   | app        | 不要             | 書籍カード（画像・タイトル・著者・ジャンル・平均評価）、ページ送り、「書籍を登録」ボタン                 | F-01        |
| SC-02  | 書籍詳細       | PG02   | `/books/{book}`          | `books.show`      | `books/show.blade.php`    | app        | 不要             | 書籍情報、お気に入り、編集・削除（作成者のみ）、レビュー投稿フォーム、レビュー一覧（いいね・編集・削除） | F-02・F-05・F-11・F-13〜F-15 |
| SC-03  | 書籍登録       | PG03   | `/books/create`          | `books.create`    | `books/create.blade.php`（`books/_form.blade.php`） | app | 必要             | 書籍の入力フォーム（ジャンルはチェックボックス）                                                       | F-03        |
| SC-04  | 書籍編集       | PG04   | `/books/{book}/edit`     | `books.edit`      | `books/edit.blade.php`（`books/_form.blade.php`）   | app | 必要＋作成者のみ | 書籍の入力フォーム（登録内容を初期表示）                                                               | F-04        |
| SC-05  | ジャンル一覧   | PG05   | `/genres`                | `genres.index`    | `genres/index.blade.php`  | app        | 必要             | ジャンル名・書籍数の表、編集・削除、「ジャンルを登録」ボタン                                           | F-06・F-10  |
| SC-06  | ジャンル詳細   | PG06   | `/genres/{genre}`        | `genres.show`     | `genres/show.blade.php`   | app        | 必要             | そのジャンルの書籍カード（画像・タイトル・著者・ジャンル）、ページ送り                                 | F-07        |
| SC-07  | ジャンル登録   | PG07   | `/genres/create`         | `genres.create`   | `genres/create.blade.php` | app        | 必要             | ジャンル名の入力フォーム                                                                               | F-08        |
| SC-08  | ジャンル編集   | PG08   | `/genres/{genre}/edit`   | `genres.edit`     | `genres/edit.blade.php`   | app        | 必要             | ジャンル名の入力フォーム（登録内容を初期表示）                                                         | F-09        |
| SC-09  | レビュー編集   | PG09   | `/reviews/{review}/edit` | `reviews.edit`    | `reviews/edit.blade.php`  | app        | 必要＋投稿者のみ | 対象の書籍名、評価（ラジオボタン）・コメントの入力フォーム                                             | F-12        |
| SC-10  | お気に入り一覧 | PG10   | `/favorites`             | `favorites.index` | `favorites/index.blade.php` | app      | 必要             | お気に入り書籍（画像・タイトル・著者・ISBN）、解除ボタン、ページ送り                                   | F-15・F-16  |
| SC-11  | ランキング     | PG11   | `/ranking`               | `ranking.index`   | `ranking/index.blade.php` | app        | 不要             | 平均評価 TOP10（順位・画像・タイトル・著者・平均評価・レビュー件数）                                   | F-17        |
| SC-12  | ログイン       | PG12   | `/login`                 | `login`           | `auth/login.blade.php`    | guest      | 不要             | メールアドレス・パスワードの入力フォーム、会員登録へのリンク                                           | F-19        |
| SC-13  | 会員登録       | PG13   | `/register`              | `register`        | `auth/register.blade.php` | guest      | 不要             | お名前・メールアドレス・パスワード・パスワード確認の入力フォーム、ログインへのリンク                   | F-18        |

- レイアウト：app は `components/app-layout.blade.php`（上部にナビゲーション `layouts/navigation.blade.php` が付く）、guest は `components/guest-layout.blade.php`（ロゴだけ）。
- 「認証 必要」の画面を未ログインで開いたときは、ログイン画面へ移動する想定（Q-10）。
- 「作成者のみ」「投稿者のみ」の画面をほかのユーザーが開いたときの扱いは Q-47。

### 画面の見出し

ビューの見出しは、要件の画面名と違うものがある。画面名は要件に合わせ、見出しはビューのまま使う。

| 画面ID | 見出し（ビュー）                 |
| ------ | -------------------------------- |
| SC-01  | 書籍一覧                         |
| SC-02  | 書籍のタイトル                   |
| SC-03  | 書籍の登録                       |
| SC-04  | 書籍の編集                       |
| SC-05  | ジャンル管理                     |
| SC-06  | ジャンル: （ジャンル名）         |
| SC-07  | ジャンル登録                     |
| SC-08  | ジャンル編集                     |
| SC-09  | レビューの編集                   |
| SC-10  | お気に入り一覧                   |
| SC-11  | 評価ランキング TOP 10            |
| SC-12  | （見出しなし。ロゴとフォームのみ） |
| SC-13  | （見出しなし。ロゴとフォームのみ） |

## 画面を持たない操作

フォームの送信先で、画面は持たない。処理後の移動先は `docs/screen-transitions.md` の「処理後の移動先」を参照。

| 操作             | ルート名           | 操作する画面        | 確認ダイアログ | 機能 |
| ---------------- | ------------------ | ------------------- | -------------- | ---- |
| 書籍登録         | `books.store`      | SC-03               | なし           | F-03 |
| 書籍更新         | `books.update`     | SC-04               | なし           | F-04 |
| 書籍削除         | `books.destroy`    | SC-02               | あり           | F-05 |
| ジャンル登録     | `genres.store`     | SC-07               | なし           | F-08 |
| ジャンル更新     | `genres.update`    | SC-08               | なし           | F-09 |
| ジャンル削除     | `genres.destroy`   | SC-05               | あり           | F-10 |
| レビュー投稿     | `reviews.store`    | SC-02               | なし           | F-11 |
| レビュー更新     | `reviews.update`   | SC-09               | なし           | F-12 |
| レビュー削除     | `reviews.destroy`  | SC-02               | あり           | F-13 |
| いいね切り替え   | `reviews.like`     | SC-02               | なし           | F-14 |
| お気に入り切り替え | `favorites.toggle` | SC-02・SC-10      | なし           | F-15 |
| ログイン         | `login`（POST）    | SC-12               | なし           | F-19 |
| 会員登録         | `register`（POST） | SC-13               | なし           | F-18 |
| ログアウト       | `logout`           | ナビゲーション      | なし           | F-20 |

確認ダイアログはブラウザ標準の `confirm('本当に削除しますか？')`。キャンセルすると送信しない。

## 画面一覧に含めないもの

| 対象                                 | 理由                                                                 |
| ------------------------------------ | -------------------------------------------------------------------- |
| `welcome.blade.php`                  | Laravel 初期画面のまま。使わないため削除する（Q-01）                         |
| `components/*.blade.php`             | 画面の部品（レイアウト・ボタン・入力欄など）                         |
| `layouts/navigation.blade.php`       | 画面の部品（app レイアウトのナビゲーション）                         |
| 403・404 などのエラー画面            | ビューが無い（Q-47）                                                 |
| ★応用機能の画面（★PG14〜★PG18）     | 今回の範囲外（`docs/features.md` の「今回作らないもの」）            |
