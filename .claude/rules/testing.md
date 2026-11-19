# テストの決まり

## 実行・報告

- 機能を実装する際は対応するテストも書く。
- `./vendor/bin/sail test` を実行し、失敗があれば原因を修正して再実行し、全テストが通ることを確認する。
- 作業完了時はテスト結果(実行件数・成功/失敗)を報告する。

## 配置・分類

- モデルのリレーション等の単体テストは `tests/Unit/Models/{モデル名}Test.php`(例: `ContactTest`, `CategoryTest`, `TagTest`)に置く。
- 画面・エンドポイント単位の機能テストは `tests/Feature/{対象}Test.php`(例: `ContactFormTest`)に置く。
- DBの制約(ON DELETE CASCADE、UNIQUE等)は `tests/Feature/DatabaseConstraintsTest.php` にまとめる。
- Seederの結果検証は `tests/Feature/DatabaseSeederTest.php` にまとめる。

## 書き方

- テストクラスは `Tests\TestCase` を継承し、DBにアクセスする場合は `RefreshDatabase` を使う。
- テストメソッド名は `test_` + スネークケースの英語で、何を検証するかが読んで分かるようにする(例: `test_confirm_fails_when_first_name_is_missing`)。
- テストデータは `Model::factory()` で作成する。
- バリデーションエラーは `assertSessionHasErrors(['field' => '期待する日本語メッセージ'])` のように、フィールド名とエラーメッセージの文言(日本語)の両方を検証する。
- DBへの反映は `assertDatabaseHas` / `assertDatabaseMissing` / `assertDatabaseCount` で検証する。
- コメントは書かない。挙動が非自明な場合(例: 提供済みBladeの実装都合でアサーションの書き方を変えた理由)のみ、その理由を1行で書く。
