# テスト観点

何を確かめるかを、抜けが出ないように並べたもの。範囲は基本機能のみ。
人が手で試す行は `docs/acceptance-criteria.md` に絞り、網羅はこちらに置く。観点までを書き、入力値の組み合わせは書き出さない。

- ID の単位の番号は、`docs/acceptance-criteria.md` と同じ。16 は、画面をまたぐ観点。
- 区分は、正常系・異常系・境界値のどれか。
- 想定（`docs/questions.md` の Q-xx）にもとづく観点は、「何を確かめるか」に Q-xx を書く。回答が想定と違ったら、その観点を直す。

## 1. 会員登録画面

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-1-1 | 正しい入力で `users` に行が増え、ログイン状態で書籍一覧へ移動する（Q-08） | 正常系 | `docs/screen-transitions.md`、`docs/crud-matrix.md` |
| TP-1-2 | パスワードがハッシュ化されて保存される | 正常系 | `docs/table-definitions.md` |
| TP-1-3 | お名前・メールアドレス・パスワードが空のとき、項目ごとのメッセージで戻る | 異常系 | `docs/screen-input-checks.md` |
| TP-1-4 | メールアドレスの形式が正しくないときに弾く | 異常系 | `docs/screen-input-checks.md` |
| TP-1-5 | 登録済みのメールアドレスを弾く。大文字と小文字だけが違うものも弾く | 異常系 | `docs/screen-input-checks.md`、`docs/table-definitions.md` |
| TP-1-6 | パスワードとパスワード確認が違うときに弾き、エラーはパスワード欄の下に出る | 異常系 | `docs/screen-input-checks.md` |
| TP-1-7 | お名前・メールアドレスは 255 文字で通り、256 文字で弾く | 境界値 | `docs/validation-rules.md` |
| TP-1-8 | パスワードは 8 文字で通り、7 文字で弾く | 境界値 | `docs/validation-rules.md` |
| TP-1-9 | エラーで戻ったとき、お名前・メールアドレスは残り、パスワードは残らない | 異常系 | `docs/screen-input-checks.md`、`docs/screen-transitions.md` |
| TP-1-10 | ログイン中は会員登録画面を開けず、書籍一覧へ移動する（Q-08） | 異常系 | `docs/permission-matrix.md` |

## 2. ログイン画面

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-2-1 | 正しい組み合わせでログインでき、書籍一覧へ移動する（Q-08） | 正常系 | `docs/screen-transitions.md` |
| TP-2-2 | 認証が必要な画面から来たときは、ログイン後に元の画面へ戻る（Q-10） | 正常系 | `docs/sequence-diagrams.md`、`docs/screen-transitions.md` |
| TP-2-3 | メールアドレス・パスワードが空のとき、項目ごとのメッセージで戻る | 異常系 | `docs/screen-input-checks.md` |
| TP-2-4 | メールアドレスが未登録のときと、パスワードが違うときで、同じメッセージが出る | 異常系 | `docs/screen-input-checks.md` |
| TP-2-5 | 1 分に 5 回までは通常どおり処理され、6 回目は 429 のエラー画面になる（Q-60） | 境界値 | `docs/error-handling.md` |
| TP-2-6 | ログイン中はログイン画面を開けず、書籍一覧へ移動する（Q-08） | 異常系 | `docs/permission-matrix.md`、`docs/routing.md` |

## 3. 書籍一覧画面（トップ）

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-3-1 | 未ログインでも開ける | 正常系 | `docs/permission-matrix.md` |
| TP-3-2 | `/` で開け、`GET /books` のルートは無い（Q-01） | 正常系 | `docs/routing.md` |
| TP-3-3 | 登録日時の新しい順に並び、同じ日時は ID の大きい順になる（Q-13・Q-50） | 正常系 | `docs/questions.md`、`docs/table-definitions.md` |
| TP-3-4 | 平均評価が、レビューのある書籍と無い書籍で、ビューのとおりに表示される | 正常系 | `docs/screen-items.md` |
| TP-3-5 | 10 件までは 1 ページに収まり、11 件で 2 ページになる | 境界値 | `docs/features.md` |
| TP-3-6 | 0 件のとき「書籍が登録されていません。」が出る | 境界値 | `books/index.blade.php`（文言は設計書に無い） |
| TP-3-7 | 書籍の件数が増えても、実行する SQL の数が増えない（N+1 が無い） | 正常系 | `docs/screen-items.md` |

## 4. 書籍詳細画面

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-4-1 | 未ログイン・ログイン中・作成者・投稿者で、操作の出し分けがビューのとおりになる | 正常系 | `docs/screen-items.md`、`docs/permission-matrix.md` |
| TP-4-2 | 説明・画像URLが無い書籍で、説明欄が出ず「画像なし」が出る | 正常系 | `docs/screen-items.md` |
| TP-4-3 | レビューが新しい順に全件出る（Q-14・Q-50） | 正常系 | `docs/questions.md` |
| TP-4-4 | レビューが 0 件のとき「まだレビューはありません。」が出る | 境界値 | `docs/screen-items.md` |
| TP-4-5 | 投稿日が日本時間の日付で出る（Q-51） | 境界値 | `docs/questions.md` |
| TP-4-6 | コメントなしで投稿すると、書籍詳細に戻り、メッセージと選んだ評価が残る（Q-21） | 異常系 | `docs/validation-rules.md`、`docs/screen-items.md` |
| TP-4-7 | 同じ人が同じ書籍に 2 件以上投稿できる（Q-20） | 正常系 | `docs/er-diagram.md` |
| TP-4-8 | 評価を選ばずに投稿すると、書籍詳細に戻り、メッセージと入力したコメントが残る | 異常系 | `docs/screen-input-checks.md` |
| TP-4-9 | 評価は 1・5 で通り、0・6 と数字以外を弾く | 境界値 | `docs/validation-rules.md` |
| TP-4-10 | コメントは 1000 文字で通り、1001 文字で弾く | 境界値 | `docs/validation-rules.md` |
| TP-4-11 | 投稿者が自分のレビューを削除でき、そのレビューのいいねも消える（Q-12・Q-44） | 正常系 | `docs/crud-matrix.md` |
| TP-4-12 | 投稿者以外のレビュー削除の送信は 403 になり、消えない（Q-47） | 異常系 | `docs/permission-matrix.md`、`docs/error-handling.md` |
| TP-4-13 | いいねの切り替えで `review_likes` の行が増減し、件数が変わる。自分のレビューにもいいねできる（Q-22） | 正常系 | `docs/crud-matrix.md`、`docs/sequence-diagrams.md` |
| TP-4-14 | お気に入りの切り替えで `favorites` の行が増減し、ハートの表示が変わり、書籍詳細へ戻る（Q-45） | 正常系 | `docs/sequence-diagrams.md` |
| TP-4-15 | 未ログインで、いいね・お気に入り・レビュー投稿を直接送ると、保存されずログイン画面へ移動する | 異常系 | `docs/permission-matrix.md`、`docs/error-handling.md` |
| TP-4-16 | 書籍を削除すると、`book_genre`・`favorites`・`reviews`・そのレビューの `review_likes` が一緒に消える（Q-12） | 正常系 | `docs/crud-matrix.md`、`docs/er-diagram.md` |
| TP-4-17 | 削除の確認ダイアログでキャンセルすると、送信されず何も消えない | 正常系 | `docs/screen-list.md`、`docs/sequence-diagrams.md` |
| TP-4-18 | 作成者以外の書籍削除の送信は 403 になり、消えない（Q-47） | 異常系 | `docs/permission-matrix.md` |
| TP-4-19 | 存在しない ID の書籍詳細は 404 になる | 異常系 | `docs/error-handling.md` |
| TP-4-20 | ビューのリレーション名で読めて、レビュー件数が増えても SQL の数が増えない（N+1 が無い） | 正常系 | `docs/screen-items.md`、`docs/er-diagram.md` |

## 5. 書籍登録画面

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-5-1 | 登録すると `books` と `book_genre` に行が増え、`user_id` がログイン中のユーザーになる | 正常系 | `docs/crud-matrix.md` |
| TP-5-2 | 説明・画像URLが空でも登録でき、`null` で保存される | 正常系 | `docs/validation-rules.md`、`docs/table-definitions.md` |
| TP-5-3 | ジャンルを複数選んで登録できる | 正常系 | `docs/validation-rules.md` |
| TP-5-4 | 必須項目（タイトル・著者・ISBN・出版日・ジャンル）が空のとき、項目ごとのメッセージで戻る | 異常系 | `docs/screen-input-checks.md` |
| TP-5-5 | タイトル・著者は 255 文字で通り、256 文字で弾く | 境界値 | `docs/validation-rules.md` |
| TP-5-6 | ISBN は 13 桁で通り、12 桁・14 桁・数字以外・ハイフン入りを弾く | 境界値 | `docs/validation-rules.md` |
| TP-5-7 | 登録済みの ISBN を弾く | 異常系 | `docs/screen-input-checks.md` |
| TP-5-8 | 出版日は、ありえない日付（2 月 30 日など）と `YYYY-MM-DD` 以外の形式（`January 2 2024` など）を弾き、未来の日付は通る | 異常系 | `docs/validation-rules.md` |
| TP-5-9 | 説明は 2000 文字で通り、2001 文字で弾く | 境界値 | `docs/validation-rules.md` |
| TP-5-10 | 画像URLは 255 文字で通り、256 文字と URL の形式でないものを弾く | 境界値 | `docs/validation-rules.md` |
| TP-5-11 | 存在しないジャンル ID・配列でない値を送ると弾く | 異常系 | `docs/screen-input-checks.md` |
| TP-5-12 | エラーで戻ったとき、入力値とジャンルの選択が残る | 異常系 | `docs/screen-items.md` |
| TP-5-13 | ジャンルが 0 件のとき、「ジャンルが登録されていません。先にジャンルを登録してください。」が出る | 境界値 | `docs/screen-items.md` |
| TP-5-14 | `book_genre` の書き込みで失敗すると、`books` の行も残らない（Q-05） | 異常系 | `docs/error-handling.md` |
| TP-5-15 | 未ログインでは開けず、送信しても保存されない | 異常系 | `docs/permission-matrix.md` |

## 6. 書籍編集画面

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-6-1 | 登録内容が初期値に入り、出版日が `YYYY-MM-DD` で入り、紐付いたジャンルが選択されている | 正常系 | `docs/screen-items.md` |
| TP-6-2 | 更新すると `books` が変わり、書籍詳細へ移動する（Q-15） | 正常系 | `docs/screen-transitions.md` |
| TP-6-3 | ジャンルを変えると、外したものは消え、選んだものが増える | 正常系 | `docs/crud-matrix.md` |
| TP-6-4 | ISBN を変えずに更新できる（自分は重複に数えない） | 正常系 | `docs/validation-rules.md` |
| TP-6-5 | ほかの書籍の ISBN に変えると弾く | 異常系 | `docs/validation-rules.md` |
| TP-6-6 | 入力チェックの中身は、書籍登録と同じ | 異常系 | `docs/validation-rules.md` |
| TP-6-7 | キャンセルで書籍詳細へ戻り、何も変わらない | 正常系 | `docs/screen-items.md` |
| TP-6-8 | 作成者以外は、編集画面も更新の送信も 403 になり、保存されない（Q-47） | 異常系 | `docs/permission-matrix.md`、`docs/error-handling.md` |
| TP-6-9 | 作成者以外が正しくない入力を直接送ると、入力エラーになり、保存されない（Q-52） | 異常系 | `docs/sequence-diagrams.md` |
| TP-6-10 | 未ログインで開くと、403 ではなくログイン画面へ移動する | 異常系 | `docs/error-handling.md` |
| TP-6-11 | 存在しない ID は 404 になる | 異常系 | `docs/error-handling.md` |
| TP-6-12 | 途中で失敗すると、`books` の更新も残らない（Q-05） | 異常系 | `docs/error-handling.md` |

## 7. レビュー編集画面

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-7-1 | 書籍名と、評価・コメントの初期値が入っている | 正常系 | `docs/screen-list.md` |
| TP-7-2 | 更新すると、そのレビューの書籍の書籍詳細へ移動する（Q-44） | 正常系 | `docs/screen-transitions.md` |
| TP-7-3 | コメントを空にして更新すると、編集画面へ戻り、メッセージが出る（Q-21） | 異常系 | `docs/validation-rules.md` |
| TP-7-4 | 評価は 1・5 で通り、0・6 を直接送ると弾く | 境界値 | `docs/validation-rules.md` |
| TP-7-5 | コメントは 1000 文字で通り、1001 文字で弾き、編集画面へ戻る | 境界値 | `docs/validation-rules.md` |
| TP-7-6 | キャンセルで書籍詳細へ戻り、何も変わらない | 正常系 | `docs/screen-transitions.md` |
| TP-7-7 | 投稿者以外は、編集画面も更新の送信も 403 になり、保存されない（Q-47） | 異常系 | `docs/permission-matrix.md` |
| TP-7-8 | 未ログインで開くと、ログイン画面へ移動する | 異常系 | `docs/permission-matrix.md` |
| TP-7-9 | 存在しない ID は 404 になる | 異常系 | `docs/error-handling.md` |

## 8. お気に入り一覧画面

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-8-1 | ログイン中のユーザーのお気に入りだけが出る | 正常系 | `docs/permission-matrix.md` |
| TP-8-2 | お気に入りに追加した新しい順に並び、書籍の登録順ではない（Q-23） | 正常系 | `docs/table-definitions.md` |
| TP-8-3 | 解除すると、お気に入り一覧へ戻り、その行が消える（Q-45） | 正常系 | `docs/sequence-diagrams.md` |
| TP-8-4 | 10 件までは 1 ページに収まり、11 件で 2 ページになる | 境界値 | `docs/features.md` |
| TP-8-5 | 0 件のとき「お気に入りに登録された書籍はありません。」と「書籍一覧を見る」が出る | 境界値 | `docs/screen-transitions.md` |
| TP-8-6 | 最後のページの最後の 1 冊を解除すると、書籍がある最後のページへ移動する（Q-56） | 境界値 | `docs/questions.md` |
| TP-8-7 | `?page=99` を開くと、書籍がある最後のページへ移動する（Q-56） | 異常系 | `docs/questions.md` |
| TP-8-8 | 未ログインでは開けない | 異常系 | `docs/permission-matrix.md` |

## 9. ジャンル一覧画面

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-9-1 | 全件が名前順で、ページ分けせずに出る（Q-19） | 正常系 | `docs/questions.md` |
| TP-9-2 | 書籍数が、紐付いている書籍の数と合う | 正常系 | `docs/screen-items.md` |
| TP-9-3 | 書籍が紐付いていないジャンルを削除できる | 正常系 | `docs/sequence-diagrams.md` |
| TP-9-4 | 書籍が紐付いているジャンルは削除できず、エラーメッセージが出る（Q-17） | 異常系 | `docs/sequence-diagrams.md`、`docs/screen-input-checks.md` |
| TP-9-5 | ジャンルを作っていない会員でも、編集・削除ができる（Q-16） | 正常系 | `docs/permission-matrix.md` |
| TP-9-6 | 確認ダイアログでキャンセルすると、削除されない | 正常系 | `docs/sequence-diagrams.md` |
| TP-9-7 | 0 件のとき「ジャンルが登録されていません。」が出る | 境界値 | `genres/index.blade.php`（文言は設計書に無い） |
| TP-9-8 | 未ログインでは、一覧も削除の送信も使えず、ログイン画面へ移動する | 異常系 | `docs/permission-matrix.md` |

## 10. ジャンル詳細画面

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-10-1 | そのジャンルが紐付いた書籍だけが、新しい順に出る（Q-19・Q-50） | 正常系 | `docs/questions.md`、`docs/crud-matrix.md` |
| TP-10-2 | 各書籍のカードに、選んだジャンル以外のジャンルも出る | 正常系 | `docs/crud-matrix.md` |
| TP-10-3 | 10 件までは 1 ページに収まり、11 件で 2 ページになる | 境界値 | `docs/features.md` |
| TP-10-4 | 0 件のとき「このジャンルの書籍はまだ登録されていません。」が出る | 境界値 | `genres/show.blade.php`（文言は設計書に無い） |
| TP-10-5 | 「← 書籍一覧に戻る」で、ジャンル一覧ではなく書籍一覧へ移動する | 正常系 | `docs/screen-transitions.md` |
| TP-10-6 | 未ログインでは開けない | 異常系 | `docs/permission-matrix.md` |
| TP-10-7 | 存在しない ID は 404 になる | 異常系 | `docs/error-handling.md` |

## 11. ジャンル登録画面

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-11-1 | 登録すると `genres` に行が増え、ジャンル一覧へ移動する（Q-46） | 正常系 | `docs/screen-transitions.md` |
| TP-11-2 | 空のときに弾く | 異常系 | `docs/screen-input-checks.md` |
| TP-11-3 | 50 文字で通り、51 文字で弾く | 境界値 | `docs/validation-rules.md` |
| TP-11-4 | 同じ名前を弾く | 異常系 | `docs/screen-input-checks.md` |
| TP-11-5 | 大文字と小文字・全角と半角・ひらがなとカタカナ・濁点と半濁点だけが違う名前も、同じ名前として弾く（Q-48） | 境界値 | `docs/table-definitions.md`、`docs/questions.md` |
| TP-11-6 | キャンセルでジャンル一覧へ戻り、何も増えない | 正常系 | `docs/screen-transitions.md` |
| TP-11-7 | 未ログインでは開けず、送信しても保存されない | 異常系 | `docs/permission-matrix.md` |

## 12. ジャンル編集画面

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-12-1 | 今の名前が初期値に入っている | 正常系 | `docs/screen-list.md` |
| TP-12-2 | 更新すると、ジャンル一覧へ移動し、そのジャンルの書籍の表示も新しい名前になる（Q-46） | 正常系 | `docs/screen-transitions.md`、`docs/crud-matrix.md` |
| TP-12-3 | 名前を変えずに更新できる（自分は重複に数えない） | 正常系 | `docs/validation-rules.md` |
| TP-12-4 | ほかのジャンルと同じ名前に変えると弾く | 異常系 | `docs/validation-rules.md` |
| TP-12-5 | 50 文字で通り、51 文字で弾き、編集画面へ戻る | 境界値 | `docs/validation-rules.md` |
| TP-12-6 | 未ログインでは開けず、存在しない ID は 404 になる | 異常系 | `docs/permission-matrix.md`、`docs/error-handling.md` |

## 13. ランキング画面

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-13-1 | 未ログインでも開ける | 正常系 | `docs/permission-matrix.md` |
| TP-13-2 | 平均評価の高い順に並び、同じなら件数の多い順、それも同じなら ID の大きい順になる（Q-24・Q-50） | 正常系 | `docs/questions.md` |
| TP-13-3 | レビューが無い書籍は対象にならない（Q-24） | 境界値 | `docs/crud-matrix.md` |
| TP-13-4 | 10 件までは全件出て、11 件以上のときは上位 10 件だけが出る | 境界値 | `docs/features.md` |
| TP-13-5 | 対象が 0 件のとき「まだレビューが投稿された書籍がありません。」が出る | 境界値 | `ranking/index.blade.php`（文言は設計書に無い） |
| TP-13-6 | 書籍の件数が増えても、SQL の数が増えない（N+1 が無い） | 正常系 | `docs/screen-items.md` |

## 14. ログアウト

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-14-1 | ログアウトすると、ログイン画面へ移動し、セッションが無効になる（Q-07・Q-08） | 正常系 | `docs/routing.md`、`docs/screen-transitions.md` |
| TP-14-2 | どのテーブルも書き換えない | 正常系 | `docs/crud-matrix.md` |
| TP-14-3 | `GET /logout` では、ログアウトされない | 異常系 | `docs/routing.md` |
| TP-14-4 | 未ログインで `POST /logout` を送ると、ログイン画面へ移動する | 異常系 | `docs/permission-matrix.md` |

## 15. 公開API

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-15-1 | 一覧は、指定が無ければ 20 件で、`data`・`links`・`meta` の形で返る | 正常系 | `docs/api-spec.md` |
| TP-15-2 | 書籍の項目（型・形式）が仕様のとおりで、`published_date` は `YYYY-MM-DD`、未入力は `null`、`isbn` は文字列（Q-58-1・Q-58-2） | 正常系 | `docs/api-spec.md` |
| TP-15-3 | `user` にメールアドレスが入らない（Q-58-4） | 異常系 | `docs/api-spec.md` |
| TP-15-4 | 登録日時の新しい順で、同じ日時は ID の大きい順になる（Q-50） | 正常系 | `docs/api-spec.md`、`docs/questions.md` |
| TP-15-5 | キーワードは、タイトルと著者の部分一致で絞れる | 正常系 | `docs/api-spec.md` |
| TP-15-6 | ジャンル ID で絞れて、キーワードと一緒に指定すると両方に当てはまるものだけが返る（Q-55） | 正常系 | `docs/api-spec.md`、`docs/questions.md` |
| TP-15-7 | キーワードの `%`・`_` が、普通の文字として検索される（Q-55） | 境界値 | `docs/questions.md` |
| TP-15-8 | 当てはまるものが無いときは、200 で `data` が空になる | 境界値 | `docs/api-spec.md` |
| TP-15-9 | `per_page` は 1・100 で通り、0・101 で 422 になる | 境界値 | `docs/validation-rules.md` |
| TP-15-10 | `page` は 1 で通り、0 で 422 になる。キーワードは 255 文字で通り、256 文字で 422 になる | 境界値 | `docs/validation-rules.md` |
| TP-15-11 | 存在しないジャンル ID は 422 になる | 異常系 | `docs/validation-rules.md` |
| TP-15-12 | 詳細は 200 で書籍を返し、存在しない ID は 404 になる | 正常系 | `docs/api-spec.md` |
| TP-15-13 | 登録は 201 で、`books` と `book_genre` に行が増え、`user_id` が入力の値になる（Q-26） | 正常系 | `docs/api-spec.md`、`docs/sequence-diagrams.md` |
| TP-15-14 | `user_id` が無い・存在しないときは 422 になる（Q-26） | 異常系 | `docs/validation-rules.md`、`docs/api-spec.md` |
| TP-15-15 | 登録・更新の書籍の項目は、Web と同じ入力チェックで、エラーは日本語の `errors` で返る（Q-28） | 異常系 | `docs/validation-rules.md`、`docs/api-spec.md` |
| TP-15-16 | 更新は 200 で更新後を返し、ジャンルが入れ替わる | 正常系 | `docs/api-spec.md` |
| TP-15-17 | 更新で `user_id` を送ると登録者が変わり、送らなければ変わらない。存在しない ID は 422 になる（Q-41） | 正常系 | `docs/api-spec.md` |
| TP-15-18 | 更新で必須の項目を送らないと 422 になる。`PATCH` も同じ | 異常系 | `docs/api-spec.md`、`docs/routing.md` |
| TP-15-19 | 更新で ISBN を変えなくても通り、ほかの書籍の ISBN は 422 になる | 異常系 | `docs/validation-rules.md` |
| TP-15-20 | 削除は 204 で本文が無く、関連する行も一緒に消える（Q-12） | 正常系 | `docs/api-spec.md`、`docs/crud-matrix.md` |
| TP-15-21 | 更新・削除で存在しない ID は 404 になる | 異常系 | `docs/api-spec.md` |
| TP-15-22 | ログインせず、CSRF トークンも無しで、登録・更新・削除ができる | 正常系 | `docs/permission-matrix.md`、`docs/sequence-diagrams.md` |
| TP-15-23 | `Accept: application/json` を付けなくても、エラーが JSON で返る | 異常系 | `docs/error-handling.md` |
| TP-15-24 | 404（書籍・無いパス）・405 の `message` が日本語で、クラス名が入らない（Q-53） | 異常系 | `docs/error-handling.md` |
| TP-15-25 | 422 の `message` の残りの件数の部分が日本語になる（Q-53） | 異常系 | `docs/error-handling.md`、`docs/api-spec.md` |
| TP-15-26 | 1 分に 60 回までは通り、61 回目で 429 になる | 境界値 | `docs/api-spec.md`、`docs/error-handling.md` |
| TP-15-27 | 500 のとき、JSON の `message` だけを返し、エラーの詳細を返さない（本番の設定） | 異常系 | `docs/error-handling.md` |
| TP-15-28 | 一覧の各書籍に `reviews_avg_rating`・`reviews_count` が入り、値がその書籍のレビューの平均・件数と合う（Q-58-1） | 正常系 | `docs/api-spec.md` |
| TP-15-29 | レビューが無い書籍は、一覧の `reviews_avg_rating` が `null`、`reviews_count` が 0 になる | 境界値 | `docs/api-spec.md` |
| TP-15-30 | 詳細の `reviews` に全件が新しい順で入り、`user` にメールアドレスが入らない（Q-14・Q-58-3・Q-58-4） | 正常系 | `docs/api-spec.md` |
| TP-15-31 | レビューが無い書籍は、詳細の `reviews` が空の配列、`reviews_avg_rating` が `null`、`reviews_count` が 0 になる | 境界値 | `docs/api-spec.md` |
| TP-15-32 | 登録・更新のレスポンスは書籍詳細と同じ形で、`reviews_avg_rating`・`reviews_count`・`reviews` が入る（Q-57） | 正常系 | `docs/api-spec.md` |

## 16. 画面をまたぐ観点

| ID | 何を確かめるか | 区分 | 元になった設計書 |
| --- | --- | --- | --- |
| TP-16-1 | ビューの `route('books.index')` などが Web の URL を指し、公開API の URL にならない | 正常系 | `docs/routing.md` |
| TP-16-2 | `/books/create`・`/genres/create` が、詳細画面として扱われない | 正常系 | `docs/routing.md` |
| TP-16-3 | 成功メッセージは 1 回だけ表示され、再読み込みで消える | 正常系 | `docs/screen-transitions.md` |
| TP-16-4 | 成功メッセージの文言が決めたとおりで、9 種類とも出る（Q-54） | 正常系 | `docs/questions.md` |
| TP-16-5 | いいね・お気に入り・ログイン・会員登録・ログアウトでは、メッセージが出ない | 正常系 | `docs/screen-transitions.md` |
| TP-16-6 | 未ログインのとき、ナビゲーションに「書籍登録」「お気に入り」「ジャンル管理」が出て、押すとログイン画面へ移動する（Q-10） | 正常系 | `docs/screen-transitions.md` |
| TP-16-7 | セッションが切れたあとに送信すると、419 の画面になる | 異常系 | `docs/error-handling.md` |
| TP-16-8 | 存在しない URL・メソッド違いは、404・405 の標準の画面になる | 異常系 | `docs/error-handling.md` |
| TP-16-9 | 予期しない例外は 500 の画面になり、ログに残る。入力エラー・403・404 はログに残らない | 異常系 | `docs/error-handling.md` |
| TP-16-10 | ほぼ同時の操作で DB の制約に当たったとき（ISBN・ジャンル名の重複、紐付いたジャンルの削除、ボタンの連打）は、データが壊れず 500 になる | 異常系 | `docs/error-handling.md` |
| TP-16-11 | シーダーを 2 回実行しても、ユーザー・ジャンル・書籍・お気に入り・いいねが重複しない | 正常系 | `docs/requirements.md` |
| TP-16-12 | シーダーの件数と内容が要件のとおりになる（ユーザー 5 件・ジャンル 10 件・書籍 11 件・レビュー 32 件） | 正常系 | `docs/requirements.md` |
