# Instagram Feed Sync v2.1.1

シンプルで完全な Instagram 投稿表示プラグイン

## ✨ 特徴

- Instagram グラフ API & 基本表示 API 対応
- トークン自動更新（毎日）
- AES-256 暗号化
- 投稿タイプ別アイコン表示
  - 動画: ▶️ 再生アイコン
  - 複数画像: 📑 カルーセルアイコン
  - 単独画像: 📷 カメラアイコン
- プロフィール表示（ユーザー名 + 自己紹介）
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
2. API 種別を選択
3. アクセストークンを入力
4. ユーザー名を入力
5. Instagram URL（オプション）を入力
6. 保存

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

- v2.1.1 (2025-01-XX)
  - アイコン表示機能追加
  - プロフィール表示をシンプル化
  - ホバーエフェクト削除
- v2.0.0 (2025-01-XX)
  - 初回リリース

開発: m yamada with AI
Instagram API: Meta Platforms, Inc.

---

**バージョン履歴**

- v2.1.1 (2025-10-05)
  - アイコン表示機能追加
  - プロフィール表示をシンプル化
  - ホバーエフェクト削除
- v2.0.0 (2025-10-04)
  - 初回リリース
