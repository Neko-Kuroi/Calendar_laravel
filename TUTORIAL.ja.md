# PHP初心者のためのLaravelカレンダーアプリケーションチュートリアル

このチュートリアルでは、Laravelフレームワークで構築されたシンプルなカレンダーアプリケーションについて解説します。主要なファイルとコンセプトを探求し、基本的なWebアプリケーションがPHPでどのように構成されているかを理解する手助けをします。

## アプリケーション概要

このアプリケーションは、イベントの作成、更新、削除ができるシングルページのカレンダーです。フロントエンドには[FullCalendar](https://fullcalendar.io/)ライブラリを使用し、バックエンドはLaravelでイベントデータを管理します。

## ディレクトリ構成

まず、このアプリケーションに関わる主要なディレクトリとファイルを見てみましょう。

```
calendar-app/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── EventController.php
│   └── Models/
│       └── Event.php
├── resources/
│   └── views/
│       └── calendar.blade.php
└── routes/
    └── web.php
```

*   `app/`: モデルやコントローラなど、アプリケーションのコアコードが含まれています。
*   `resources/`: アプリケーションのビュー（HTMLテンプレート）が格納されています。
*   `routes/`: アプリケーションのすべてのURL定義が含まれています。

## コードを理解する

それでは、具体的なファイルに飛び込み、それらがどのように連携して動作するかを理解しましょう。

### 1. `routes/web.php` - URLの定義

このファイルは、アプリケーションのURL（または「ルート」）を定義します。ブラウザでURLにアクセスすると、Laravelはこのファイルを参照して何を行うべきかを決定します。

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;

// カレンダー表示用のメインビュー
Route::get('/', function () {
    return view('calendar');
});

// APIルート
Route::prefix('api')->group(function () {
    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
});
```

*   `Route::get('/', ...)`: この行はホームページ（`/`）のルートを定義します。ユーザーがこのURLにアクセスすると, Laravelは`calendar`ビューを返します。
*   `Route::prefix('api')->group(...)`: `/api`プレフィックスの下に一連のルートをグループ化します。これらはFullCalendarライブラリがバックエンドとやり取りするために使用するルートです。
    *   `GET /api/events`: データベースからすべてのイベントを取得します。
    *   `POST /api/events`: 新しいイベントを作成します。
    *   `PUT /api/events/{event}`: 既存のイベントを更新します。
    *   `DELETE /api/events/{event}`: イベントを削除します。

### 2. `app/Http/Controllers/EventController.php` - リクエストの処理

このコントローラは、`web.php`で定義したAPIリクエストを処理する責任があります。

```php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    // 全イベントを取得
    public function index()
    {
        return Event::all();
    }

    // 新規イベントを登録
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        return Event::create($request->all());
    }

    // イベントを更新
    public function update(Request $request, Event $event)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $event->update([
            'start' => $request->input('start'),
            'end' => $request->input('end'),
        ]);

        return $event;
    }

    // イベントを削除
    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json(['status' => 'success']);
    }
}
```

*   `index()`: このメソッドは`Event`モデルを使用してデータベースからすべてのイベントを取得し、JSONレスポンスとして返します。
*   `store(Request $request)`: このメソッドは新しいイベントを作成します。まず、受信したリクエストデータを検証し、`title`、`start`、`end`フィールドが存在し、有効であることを確認します。その後��データベースに新しい`Event`を作成します。
*   `update(Request $request, Event $event)`: このメソッドは既存のイベントを更新します。`start`と`end`の日付を検証し、データベースのイベントを更新します。
*   `destroy(Event $event)`: このメソッドはデータベースからイベントを削除します。

### 3. `app/Models/Event.php` - データモデル

このファイルは、データベースの`events`テーブルを表す`Event`モデルを定義します。

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'start',
        'end',
    ];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];
}
```

*   `$fillable`: この配列は、一括割り当てが許可されているフィールドを指定します。これは、不要なフィールドが更新されるのを防ぐためのセキュリティ機能です。
*   `$casts`: この配列は、特定の属性を一般的なデータ型に変換する方法を定義します。この場合、`start`と`end`フィールドは自動的に`datetime`オブジ���クトに変換されます。

### 4. `resources/views/calendar.blade.php` - ユーザーインターフェース

これはユーザーが見るメインのビューファイルです。埋め込みJavaScriptを含む標準的なHTMLファイルです。

```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>PWA Event Calendar</title>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <style>
        /* ... CSSスタイル ... */
    </style>
</head>
<body>
    <div id="calendar"></div>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                // ... FullCalendarのオプション ...
                events: '/api/events', // APIからイベントを読み込む
                // ... イベントの作成、更新、削除のためのイベントハンドラ ...
            });
            calendar.render();
        });
    </script>
</body>
</html>
```

*   このファイルはCDNからFullCalendarライブラリをインクルードします。
*   カレンダーがレンダリングされる`calendar`というIDを持つ`div`があります。
*   JavaScriptコードはFullCalendarを初期化し、以下のイベントハンドラを設定します：
    *   **日付範囲の選択:** 新しいイベントを作成できます。
    *   **イベントのドラッグ＆ドロップ:** イベントの日付を更新します。
    *   **イベントのクリック:** イベントを削除します。

これらのイベントハンドラは、`web.php`で定義したAPIルートに`fetch`リクエストを送信します。

## アプリケーションの実行方法

このアプリケーションをローカルマシンで実行するには、PHPとComposerがインストールされている必要があります。

1.  **リポジトリをクローンする:**
    ```bash
    git clone <repository_url>
    cd calendar-app
    ```

2.  **依存関係をインストールする:**
    ```bash
    composer install
    npm install
    ```

3.  **環境ファイルを設定する:**
    ```bash
    cp .env.example .env
    ```
    データベースを作成し、`.env`ファイルをデータベースの認証情報で更新する必要があります。

4.  **アプリケー���ョンキーを生成する:**
    ```bash
    php artisan key:generate
    ```

5.  **データベースマイグレーションを実行する:**
    ```bash
    php artisan migrate
    ```

6.  **開発サーバーを起動する:**
    ```bash
    php artisan serve
    ```

これで、ブラウザを開いて`http://127.0.0.1:8000`にアクセスすると、カレンダーアプリケーションが動作しているのを確認できます。

このチュートリアルが、Laravelアプリケーションの基本を理解するのに役立ったことを願っています！
