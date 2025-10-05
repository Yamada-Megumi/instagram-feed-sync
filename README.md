# Instagram Feed Sync v2.1.13

シンプルで完全な Instagram 投稿表示プラグイン

## ✨ 特徴

- Instagram グラフ API 対応
- トークン自動更新（毎日）
- AES-256 暗号化
- 投稿タイプ別アイコン表示
  - 動画: ▶️ 再生アイコン
  - 複数画像: 📑 カルーセルアイコン
  - 単独画像: 📷 カメラアイコン
- プロフィール表示（ユーザー名 + 自己紹介）
- フォローボタン表示
- レスポンシブ対応

## 📋 要件

- WordPress: 6.0 以上
- PHP: 8.0 以上
- OpenSSL 拡張機能

## 🚀 インストール

1. ZIP ファイルを解凍
2. `instagram-feed-sync` フォルダを `wp-content/plugins/` にアップロード
4. WordPress 管理画面でプラグインを有効化
5. 管理画面のサイドバーにある「Instagram Feed」メニューから設定

## 📝 使い方

### 基本表示

```
[instagram_feed limit="12" columns="3"]
```

### プロフィール付き

```
[instagram_feed show_profile="true" limit="9" columns="3"]
```

### フォローボタン

`show_profile="true"` の場合、プロフィール情報と共にフォローボタンが表示されます。
ボタンのテキスト、背景（色またはグラデーション）、文字色は、管理画面のアカウント編集ページでカスタマイズできます。

背景には、`#3897f0` のようなカラーコードや、`linear-gradient(to right, #ff0000, #0000ff)` のようなCSSグラデーション文字列を指定できます。
文字色には、`#ffffff` のようなカラーコードを指定します。

CSSで独自にスタイルを適用したい場合は、`.instagram-follow-button-custom` クラスをターゲットにしてください。

### パラメータ

- **limit**: 表示件数（デフォルト: 12）
- **columns**: カラム数 1-6（デフォルト: 3）
- **show_profile**: プロフィール表示 true/false
- **username**: 表示するアカウント名

### 使用例

```php
<!-- サイドバー: 2カラム -->
[instagram_feed limit="6" columns="2"]

<!-- トップページ: プロフィール付き -->
[instagram_feed show_profile="true" limit="12" columns="3"]

<!-- フッター: 4カラム -->
[instagram_feed limit="8" columns="4"]

<!-- 特定アカウント -->
[instagram_feed username="myaccount" limit="9"]
```

## ⚙️ 設定方法

### 1. Meta for Developers 設定

- 設定例：ただし最新の設定情報に従ってください。

1. https://developers.facebook.com/ にアクセス
2. アプリを作成
3. instagram　テストユーザ追加　ユーザーInstaページで承認が必要
4. アクセストークンを取得

### 2. プラグイン設定

- Meta for Developers に登録後の手順です。

1. WordPress 管理画面 → Instagram Feed
2. アクセストークンを入力
3. ユーザー名を入力
4. Instagram URL（オプション）を入力
5. 保存

## 🔒 セキュリティ

- AES-256-CBC 暗号化
- CSRF 対策（wp_nonce）
- XSS 対策
- SQL インジェクション対策
- SSL 通信強制

## 🎨 表示内容

### アイコン表示

- 動画投稿: 右上に ▶️ アイコン
- 複数画像: 右上に 📑 アイコン
- 単独画像: 右上に 📷 アイコン

### プロフィール表示

`show_profile="true"` の場合:

- ユーザー名（h3 タグ）
- 自己紹介文（p タグ、絵文字・改行そのまま）
- Instagram リンク（a タグ）

## 🎨 CSSクラスガイド

- `.instagram-feed-wrapper`: フィード全体のラッパー
- `.instagram-feed-container`: 投稿グリッドのコンテナ
- `.instagram-feed-grid`: 投稿グリッド
- `.instagram-feed-item`: 各投稿
- `.instagram-icon-overlay`: 動画・カルーセルアイコンのオーバーレイ
- `.instagram-carousel-icon`: カルーセルアイコン
- `.instagram-feed-loading`: 読み込み中メッセージ
- `.instagram-feed-error`: エラーメッセージ
- `.instagram-feed-follow_btn`: フォローボタンを囲むpタグ
- `.instagram-follow-button`: フォローボタン
- `.instagram-follow-button-custom`: フォローボタン（カスタマイズ用）

## 📁 ファイル構成

```
instagram-feed-sync/
├── instagram-feed-sync.php       # メインプラグインファイル
├── README.md                     # ドキュメント
├── admin/
│   ├── index.php                 # 直接アクセス防止
│   └── css/
│       ├── admin.css            # 設定画面スタイル
│       └── index.php            # 直接アクセス防止
└── public/
  ├── index.php                # 直接アクセス防止
  ├── css/
  │   ├── style.css            # フロント用スタイル
  │   └── index.php            # 直接アクセス防止
  └── js/
    ├── script.js            # フロント用スクリプト
    └── index.php            # 直接アクセス防止
```

### トークンエラー

1. トークンの有効期限を確認
2. 必要な権限があるか確認
3. Meta for Developers で再生成

### 投稿が表示されない

1. アカウントステータスを確認
2. キャッシュをクリア
3. ブラウザコンソールでエラー確認

### アイコンが表示されない

- ブラウザキャッシュをクリア
- CSS が正しく読み込まれているか確認

## 📄 ライセンス

---

## 💬 サポート・問い合わせ

ご質問・不具合報告は [GitHub Issues](https://github.com/Yamada-Megumi/instagram-feed-sync/issues) または[github アカウント](https://github.com/Yamada-Megumi)までご連絡ください。

## 🖼️ スクリーンショット

準備中

<!--
![サンプル表示](https://your-demo-site.com/sample.png)

デモサイト: [https://your-demo-site.com/](https://your-demo-site.com/) -->

## ⚠️ 注意事項

- Instagram API の仕様変更により動作しなくなる場合があります。
- アクセストークンや個人情報の管理には十分ご注意ください。
- 商用利用時は Meta 社の利用規約もご確認ください。

## 🙏 クレジット

開発: m yamada with AI
Instagram API: Meta Platforms, Inc.

## 📄 ライセンス

GPL v2 or later

---

**バージョン履歴**

- v2.1.13 (2025-10-06)
  - `public/js/script.js` を jQuery から Vanilla JS に変更
- v2.1.12 (2025-10-06)
  - 管理画面のアカウント一覧からAPI種別の表示を削除
- v2.1.11 (2025-10-06)
  - 管理画面のアカウント編集ページから「現在のアクセストークン」表示を完全に削除
- v2.1.10 (2025-10-06)
  - 管理画面のアカウント一覧からAPI種別の表示を削除
- v2.1.9 (2025-10-06)
  - CSSクラスガイドをREADME.mdに追加
- v2.1.8 (2025-10-06)
  - フォローボタンに表示するテキストを管理画面から設定できる機能を追加
- v2.1.7 (2025-10-06)
  - フォローボタンの背景画像URL指定を削除
  - フォローボタンの文字色指定をカラーピッカーからテキスト入力に変更
- v2.1.6 (2025-10-06)
  - Instagram Basic Display APIのサポートを終了し、Instagram Graph APIのみに一本化
- v2.1.5 (2025-10-06)
  - フォローボタンの背景指定をカラーピッカーからテキスト入力に変更し、CSSグラデーションを直接指定できるように改善
- v2.1.4 (2025-10-06)
  - フォローボタンにグラデーション背景と背景画像を指定できる機能を追加
  - フォローボタンにカスタムCSS用のクラスを追加
- v2.1.3 (2025-10-06)
  - フォローボタンの背景色と文字色を管理画面から変更できる機能を追加
- v2.1.2 (2025-10-06)
  - フォローボタンを追加
- v2.1.1 (2025-10-05)
  - アイコン表示機能追加
  - プロフィール表示をシンプル化
  - ホバーエフェクト削除
- v2.0.0 (2025-10-04)
  - 初回リリース
