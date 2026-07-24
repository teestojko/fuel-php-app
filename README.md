# FuelPHP API application

FuelPHP、MySQL、Nginx、JavaScriptをDocker Composeで分離したAPIアプリケーションの土台です。

## 初回起動

1. `.env.example`を`.env`へコピーします。
2. `.env`内のプレースホルダーをローカル専用の十分に長い値へ変更します。
3. Docker Desktopを起動します。
4. `docker compose build`を実行します。
5. `docker compose run --rm api composer install`を実行します。
6. `docker compose up -d`を実行します。
7. `http://127.0.0.1:8080`をブラウザで開きます。

`.env`の内容、接続情報、認証情報をログやチャットへ貼り付けないでください。

## テスト

```bash
docker compose exec api composer test
docker compose exec frontend npm test
```

## API

`GET /api/health`はAPIとデータベースの稼働状態だけを返します。接続情報や内部例外は返しません。

## 停止

```bash
docker compose down
```

`docker compose down --volumes`はDBデータも削除するため、通常は実行しません。
