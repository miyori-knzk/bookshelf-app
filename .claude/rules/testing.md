# テストの決まり

## 書く・回す

- 実装を変えたら、テストも書いて `./vendor/bin/sail test` で回し、通るまで直して、最後に結果を報告する
- 1 クラスだけ回すときは `./vendor/bin/sail test --filter BookControllerTest` の形で回す（クラス名の例）
- スイートを分けて回すときは `--testsuite=Feature` のように指定する（`phpunit.xml` に `Unit` と `Feature` がある）
- カバレッジは `./vendor/bin/sail test --coverage` で確かめる。目標は基本機能だけで 80% 以上（`docs/questions.md` Q-04）

## 置き場所と作り

- 機能のテストは `tests/Feature/` に置く。画面（Web）も公開API も、ルートを呼んで確かめるものはこちら
- `tests/Unit/` は、アプリを起動しない単体のテスト用。`PHPUnit\Framework\TestCase` を継承するので、DB もルートも使えない
- `tests/Feature/` のクラスは `Tests\TestCase` を継承する
- メソッド名は `test_` で始めるスネークケースにし、戻り値は `: void` と書く（初期の `ExampleTest` と同じ）
- データベースを使うテストには `use RefreshDatabase;` を付ける
    - 初期の `tests/Feature/ExampleTest.php` ではコメントアウトされている。DB を使うテストを書くときに外す

## 環境

- テストは MySQL の `testing` データベース（`phpunit.xml`）で動く。SQLite には切り替えていない。回す前に `./vendor/bin/sail up -d` で `mysql` コンテナを起動しておく

## 何を確かめるか

- 確かめる観点は `docs/test-viewpoints.md`（網羅）、人が手で試す分は `docs/acceptance-criteria.md`
- 観点の元になった想定（Q-xx）の回答が変わったら、テストも直す
