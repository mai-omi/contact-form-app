# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

COACHTECH「基礎学習ターム 確認テスト」の提出物。お問い合わせフォーム(公開フォーム + 管理画面 + 公開API)をゼロから実装する課題。`resources/views/**` の Blade テンプレートは完成品として提供済みで**変更不可**(フロントエンド実装は不要)。それ以外(DB設計・ルーティング・コントローラー・バリデーション・認証・テスト)は未実装で、これから作り込む。

採点は提出された README.md・テーブル仕様・ER図とコードの一致、および `sail artisan test --coverage` のカバレッジ(目標70%)で行われる。全テストを書かなくても個別のテストケースごとに採点される。

詳細要件(画面設計・API仕様・バリデーションルール・シーディング要件・テスト要件・テーブル仕様)は `~/Desktop/COACHTECH/確認テスト要件シートのPDF.pdf` に全て定義されている。実装・レビュー時は仕様の一次情報としてこのPDFを参照すること。要点は以下の通り。

## Assumptions(前提)

- バリデーション(入力チェック)のエラーメッセージは日本語で実装する。日本語化は FormRequest の `messages()` と `lang/ja`(認証系)で行い、`laravel-lang/*` 系の外部翻訳パッケージは導入しない(サプライチェーン攻撃のリスクがあるため要件シートで禁止されている)。

## Architecture

**Traditional Web (SSR) + 独立した公開API** の二本立て。

- Web(ブラウザ向け): Blade + セッション認証(Cookie)。`routes/web.php` と通常のコントローラーを使用。
- API(応用要件): `routes/api.php` に `/api/v1/*` の公開REST APIを実装。**認証・認可は不要**(Sanctumは使わない)。Web版とAPI版は別コントローラー/別FormRequestとして分離する(例: `ContactController` と `Api\V1\ContactController`)。

認証(ログイン/登録)は **Laravel Fortify** のビューを使う(`/login`, `/register` は Fortify 提供、Blade は提供済み)。管理画面(`/admin/*`)は認証必須、未認証は `/login` にリダイレクト。

## Commands

### 環境構築(初回のみ、必ずこの手順で。採点環境もこれに準拠するため手順を変えると採点されない)

```bash
# Laravel 10.x を明示指定してプロジェクト作成済み(curl -s https://laravel.build/... は使用しない)
# Sailインストール後、MySQLを選択してpublish済み
sail up -d
sail artisan key:generate
sail npm install
sail artisan migrate --seed
# 既存DBをリセットしたい場合
sail artisan migrate:fresh --seed
```

`.env` の DB接続は `DB_HOST=mysql`(`localhost`/`127.0.0.1` ではなくDockerコンテナ名を指定すること)。phpMyAdminは `compose.yaml` に追加済み、`http://localhost:8080` でアクセス可。

### 日常の開発コマンド

```bash
sail up -d                       # コンテナ起動
./vendor/bin/sail <cmd>          # エイリアス未設定時の全コマンドはこの形
sail artisan migrate:fresh --seed
sail npm run dev                 # Vite(Tailwind)開発サーバー

sail test                        # 全テスト実行
sail artisan test --coverage     # カバレッジ測定(目標70%)
sail artisan test --filter=TestName   # 単一テスト実行
sail test tests/Feature/ContactTest.php  # ファイル単位

vendor/bin/pint                  # コードフォーマット(コミット前に必ず実行)
vendor/bin/pint --test           # フォーマット崩れチェックのみ(No findable issues were found. を確認)
```

## Testing(テスト)

- 機能を実装する際は対応するテストも書く。
- `./vendor/bin/sail test` を実行し、失敗があれば原因を修正して再実行し、全テストが通ることを確認する。
- 作業完了時はテスト結果(実行件数・成功/失敗)を報告する。

## Coding conventions (PSR-12, 要件シート「コード品質担保のための指示」準拠)

- 変数/メソッド: camelCase / クラス: PascalCase / DBテーブル: snake_case(複数形) / DBカラム: snake_case(単数形)
- モデル名: アッパーキャメル(単数) / コントローラー名・フォームリクエスト名・マイグレーションファイル名: アッパーキャメル / シーディングファイル名: アッパーキャメル
- ローマ字(例: `okyakusama`)のような単芸語ではなく英単語で命名すること
- コントローラーはリクエスト受付とレスポンス返却の道筋に専念させ、複雑なビジネスロジックはモデルやサービスクラス(任意)に逃がす
- DB接続情報やAPIキー等の設定値は必ず `.env` で管理し、コード内に直接書き込まない
- 使用していないクラス/ファイル/import、不要なコメントアウトを残さない
- コミットは機能単位(例: `feat: お問い合わせ一覧画面を実装`)、`main` へのマージも機能単位で行う

## Pull requests(PR)

PRの本文には以下を記載する。

- テスト結果(実行したテストコマンドと結果)
- 対応するIssueの受け入れ条件と、どのテストがどの条件を満たしているかの対応(突き合わせ)

## README.md 必須記載項目(提出前チェック)

プロジェクト名/概要/ER図(自作、Mermaidまたは画像)/環境構築手順/使用技術/APIエンドポイント一覧/開発環境URL/作成者。README不備は大きな減点対象。
