# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## このリポジトリは何か

BookShelf は書籍レビューアプリ（Laravel 10・PHP 8.5・MySQL 8.4）。Blade の Web 画面と、外部向けの公開API（JSON）を持つ。
決めごとは `docs/` にあり、コードより設計書が正。
実装と設計書が食い違ったら、コードではなく設計書を先に直す（ただし `docs/requirements.md` は直さない）。
設計書は、最後まで実物（実装）と一致させる。実装を変えたら、同じ作業の中で該当する設計書も直す。

## コマンド

Sail（Docker）上で動かす。`mysql`・phpMyAdmin（`http://localhost:8080`）も一緒に起動する。

```bash
./vendor/bin/sail up -d                  # 起動（アプリは http://localhost）
vendor/bin/pint                          # コミット前に必ず実行（整形してからコミット）
```

`.env` は読まない（`.claude/settings.json` で拒否している。`.env.local`・`.env.production`・`.env.testing` なども同じ）。設定値を確認したいときは `.env.example` を見る。

## 設計書（`docs/`）

実装の前に、該当する機能（F-xx）と、下の表の設計書を読むこと。設計書の全体の一覧は `docs/features.md` の「設計書」。

**`docs/requirements.md` は変更禁止。** 元の要件そのもので、他の設計書はここから起こしている。要件と食い違う点は `docs/questions.md` に確認事項として挙げる。

### 決めごとの在りか

| 決めごと                                          | 在りか                                                                         |
| ------------------------------------------------- | ------------------------------------------------------------------------------ |
| 元の要件（画面・データ・API・シード・開発ルール） | `docs/requirements.md`                                                         |
| 作る機能と、作らないもの                          | `docs/features.md`                                                             |
| 未確定の点と、置いている想定                      | `docs/questions.md`                                                            |
| 画面の一覧・項目・遷移                            | `docs/screen-list.md`、`docs/screen-items.md`、`docs/screen-transitions.md`    |
| テーブル・つながり・機能ごとの操作                | `docs/table-definitions.md`、`docs/er-diagram.md`、`docs/crud-matrix.md`       |
| URI・ルート名・コントローラー                     | `docs/routing.md`                                                              |
| 誰が何をできるか                                  | `docs/permission-matrix.md`                                                    |
| 入力チェックとエラーメッセージ                    | `docs/validation-rules.md`、`docs/screen-input-checks.md`                      |
| 公開API の入出力                                  | `docs/api-spec.md`                                                             |
| 例外の扱い                                        | `docs/error-handling.md`                                                       |
| 処理の流れ・状態遷移                              | `docs/sequence-diagrams.md`、`docs/state-transitions.md`                       |
| テスト                                            | `docs/test-viewpoints.md`（網羅）、`docs/acceptance-criteria.md`（手で試す分） |
| コマンド・環境                                    | `composer.json`、`package.json`、`compose.yaml`、`phpunit.xml`、`pint.json`    |

設計書どうしは ID でつながっている：`PG01`・`DR01`（要件の画面・データ）、`SC-01`（画面一覧）、`F-01`（機能）、`Q-01`（確認事項）、`TP-1-1`（テスト観点）。

**`docs/questions.md` の Q-xx は、回答待ちの想定を含む。** 状態欄が空のものは未確定で、設計はその想定で進めている。回答が想定と違ったら、その Q-xx を参照している設計書（テスト観点を含む）も直す。

## アーキテクチャ

Web と公開API の 2 系統を、同じモデルの上に置く。

- **Web**：`routes/web.php` → `app/Http/Controllers/` → Blade。セッション認証（Fortify）
- **公開API**：`routes/api.php` → `app/Http/Controllers/Api/V1/` → API Resource。基本機能では認証なし。エラーも必ず JSON で返す

流れはどちらも ルート → コントローラー → FormRequest・Policy → モデル → ビュー・API Resource。

**`resources/` 配下は変更禁止。** ビューは提供済みなので、それに合わせて実装する。ルート名は各ビューの `route()` の呼び出しに、コントローラーから渡す変数はビューが使っている変数に合わせる。ビュー側は変えない。

## 開発ルール（要件で決まっているもの）

原文は `docs/requirements.md`「開発ルール」。

- バリデーションは必ず FormRequest に分ける（会員登録だけは Fortify の `app/Actions/Fortify/CreateNewUser.php`、Q-09）
- 認可は必ず Policy に書き、コントローラーの `$this->authorize()` で適用する（ミドルウェアでは行わない）
- コントローラーは受付と返却に専念し、複雑な処理はモデルかサービスクラスへ
- コントローラー・モデル・FormRequest・Policy のメソッドには、引数と戻り値の型を書く。リレーションは `HasMany` などの具体的な型にする
- 主要なメソッドには PHPDoc を書く
- DB 操作は Eloquent で行う（クエリビルダや生 SQL は原則使わない）。N+1 は `with()` で防ぐ
- `foreach` より `map`・`filter` などの Collection メソッドを使う
- 機能ごとにブランチを切って main にマージする。コミットメッセージは `feat: 書籍一覧表示を実装` の形（日本語）

## プルリクエスト

本文には次を載せる。

- テストの実行結果（`sail artisan test` の結果）
- 受け入れ条件（`docs/acceptance-criteria.md` の AC-x-x）それぞれの判定。その PR で対象にした行すべてについて、合否を載せる
- 最後に `Closes #<イシュー番号>` を入れる
