
【サンプルコード】[UA プロパティの計測データを Analytics Reporting API v4 で一括エクスポート救出大作戦する](https://www.ritolab.com/entry/240)

Google Analytics における ユニバーサルアナリティクスプロパティの計測データを Analytics Reporting API v4 でエクスポートするスクリプト



## システム要件
以下がインストールされている必要があります。
- [Composer](https://getcomposer.org/)
- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
    - 動作確認は Docker Desktop for Mac のみ

## インストール

1. このスクリプトの実行に必要なパッケージをインストールします。

```shell 
composr install
```

2. イメージをビルドします。

```shell
docker compose build
```

3. credential ファイル（JSON）をプロジェクトルート直下に配置します。
```shell
proect_root/

└── credential.json
```

4. env ファイルを作成

`.env.example` を `.env` にリネームし、view ID と credential ファイル名を設定します。

```dotenv
VIEW_ID=123456789
CREDENTIAL_FILE_NAME=credential.json
```

## 実行方法

**【注意】取得期間やディメンジョン等の指定は仮置きで実装されています。必ず自身の要件に合わせて実装し直してから実行してください。**

以下のコマンドを実行します。

```shell
docker compose run --rm php-81-cli php index.php
```

データがエクスポートされ、プロジェクトルート直下に CSV ファイルとして保存されます。
