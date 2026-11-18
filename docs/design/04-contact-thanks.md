# Issue #4 設計書: サンクスページ(PG03)

## 1. 概要

GET `/thanks` にアクセスすると、`ContactController@thanks` が `contact.thanks` ビューをそのまま返す。ビジネスロジック・DBアクセスは一切なく、確認ページからのPOST後の遷移先として表示するだけのシンプルな実装である。

`ContactController@store`(Issue #3で実装済み)は既に `return redirect('/thanks');` を実装しているが、`/thanks` へのGETルート自体がこれまで存在しなかったため、確認ページで「送信」した後の遷移先は404になっていた。本Issueはこのリダイレクト先のGETルート・コントローラーアクションを新設し、Issue #3のリダイレクトを完結させる。

## 2. 受け入れ条件(ACCEPTANCE.md 3章参照)

- お問い合わせ送信後に `/thanks` を開くと、「お問い合わせありがとうございました」という送信完了メッセージが表示される。
  - `contact.thanks` ビュー(提供済み・変更不可)に既にこの文言が含まれており、`thanks()` がそのビューを返すだけで満たされる。
- サンクスページで「HOME」ボタンを押すと、初期状態(未入力)のお問い合わせフォーム入力ページ(`/`)に遷移する。
  - `thanks.blade.php` の「HOME」ボタンは単純な `<a href="/">` リンクであり、JSは絡んでいない。`resources/js/contact/init.js`・`phone-field-handler.js` にはsessionStorage等への入力値の保存・復元処理が一切なく、`index()` も常にDB由来の `$categories`・`$tags` のみを渡して空のフォームを返す(`old()`やセッションに依存する初期値の仕組みを持たない)。したがって `/` への通常GETアクセスで自動的に未入力の初期状態になり、本Issue側で追加のクリア処理を実装する必要はないと判断した。

## 3. 変えないもの

- `resources/views/contact/thanks.blade.php`・`index.blade.php`・`_form.blade.php`・`confirm.blade.php` と `resources/js/contact/**`(提供済み。変更しない)
- `ContactController@index`・`confirm`・`store`(Issue #2・#3で実装済み。本Issueでは変更しない)
- `store()` から `/thanks` へのリダイレクト処理自体(Issue #3で実装済み。本Issueはそのリダイレクト先のGETルートを新設するのみ)
- `database/migrations/**`・モデル・`database/seeders/**`(Issue #1で実装済み)
- `routes/api.php`・`config/sanctum.php` などSanctum関連ファイル

## 4. 増えるテーブル

なし。本Issueはビューの表示のみで、DBアクセスを行わない。

## 5. 増える画面

| 画面ID | 名称 | Method/Path | Controller | 備考 |
|---|---|---|---|---|
| PG03 | サンクスページ | GET `/thanks` | `ContactController@thanks` | `contact.thanks`ビューを返すのみ(新規実装)。POST `/contacts`からのリダイレクト先はIssue #3で実装済み |

## 6. 入力チェックとメッセージ一覧

該当なし。`thanks()` はGETのみでリクエストボディを扱わないため、入力チェックは発生しない。

## 7. テスト観点

- `GET /thanks` が200を返し、`contact.thanks` ビューが使われること。
- レスポンス本文に「お問い合わせありがとうございました」という送信完了メッセージが含まれること。
- レスポンス本文に `href="/"` のリンク(HOMEボタン)が含まれること。
- (参考・既存テストで確認済み)`POST /contacts` 成功時に `/thanks` へリダイレクトされること自体は `test_store_creates_contact_and_redirects_to_thanks`(Issue #3)で検証済みであり、本Issueで重複して追加しない。
- (参考・既存テストで確認済み)`GET /` が常にDB由来の選択肢のみを返しセッション等に依存しないことは `test_index_page_displays_categories_and_tags` で暗黙に確認済みであり、「HOME押下後に`/`が未入力で表示される」ことの専用テストは追加しない。
