# 月12課題 画像投稿掲示板

## 概要

Docker Compose を利用して手軽に構築できる、シンプルな画像投稿掲示板アプリケーションです。

---

## 主な機能

-   **テキスト投稿**: ユーザーは自由にメッセージを投稿できます。
-   **自動情報付与**: 各投稿には、投稿日時と連番が自動的に付与されます。
-   **画像アップロード**: 5MBを超える大きなサイズの画像もアップロード可能です。
-   **返信機能**: 過去の投稿に対してアンカー (`>>`) を付けて返信することができます。

---

## ディレクトリ構成

ec2-user/
├── Dockefile

├── compose.yml

├── nginx/

│   └── conf.d/ default.conf

└── public/ bbsimagetest.php

---

## 構築方法

1.  提出したWordファイルに記載されているIPアドレスを使用して、サーバーに接続します。
2.  以下のコマンドを実行して、アプリケーションを起動します。
    ```bash
    docker compose up -d
    ```
3.  WebブラウザでサーバーのIPアドレスにアクセスします。

---

## データベース設定 💾

アプリケーションを動作させるには、以下のSQLを実行してデータベースにテーブルを作成する必要があります。

**テーブル名**: `bbs_entries`

sql
```sql
CREATE TABLE `bbs_entries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `body` TEXT NOT NULL,
  `image_filename` TEXT DEFAULT NULL,
  `reply_to` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);
```
