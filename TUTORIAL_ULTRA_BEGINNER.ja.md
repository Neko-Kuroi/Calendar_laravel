# 超初心者のための超詳細Laravelチュートリアル

ようこそ！このガイドは、Laravelに全く触れたことのない人のためのものです。カレンダーアプリケーションを非常に小さな部品に分解して、各部分が何をしているのか、なぜそこにあるのかを理解していきましょう。

## Laravelとは？そしてMVCとは？

家を建てることを想像してみてください。設計図（計画）、建設作業員（働く人）、そして人々が見る実際の家が必要です。

*   **モデル (M):** これはあなたのデータ専門家のようなものです。アプリケーションのすべてのデータを管理します。イベントのリストを取得したり、新しいイベントを保存したりする必要がある場合は、モデルに話しかけます。私たちのアプリでは、`Event.php`がモデルです。
*   **ビュー (V):** これは人々が見る実際の家です。ユーザーインターフェース、HTML、ブラウザに表示されるウェブページです。私たちのアプリでは、`calendar.blade.php`がビューです。
*   **コントローラ (C):** これは建設作業員の監督です。ユーザーからのリクエスト（ブラウザ経由）を受け取り、モデルとビューに何をすべきかを伝えます。操作の「頭脳」です。私たちのアプリでは、`EventController.php`がコントローラです。

これが**MVC (Model-View-Controller)**パターンであり、Laravelはこれを使用してWebアプリケーションの構築をより簡単に、より整理されたものにするフレームワークです。

---

## リクエストの旅

ユーザーの旅を追って、ファイルがどのように連携して動作するかを理解しましょう。

### ステップ1：地図 - `routes/web.php`

すべてはURLから始まります。ブラウザにWebアドレスを入力すると、Laravelは「地図」を見てどこに行くべきかを確認します。この地図が`routes/web.php`ファイルです。

```php
// ユーザーがメインページ（http://your-app.com/）にアクセスしたとき
Route::get('/', function () {
    // Laravelは 'calendar' ビューを表示するように指示されます。
    return view('calendar');
});

// この部分は、カレンダーのJavaScriptがサーバーと対話するためのものです。
// アプリケーション自体専用の���特別なプライベートな入口のようなものです。
Route::prefix('api')->group(function () {
    // カレンダーがすべてのイベントを要求したとき...
    Route::get('/events', [EventController::class, 'index']);
    // カレンダーが新しいイベントを保存したいとき...
    Route::post('/events', [EventController::class, 'store']);
    // ...更新や削除についても同様です。
});
```

*   **`Route::get(...)`**: ユーザーがURLにアクセスするのを待ち受けます。
*   **`view('calendar')`**: Laravelに`resources/views`フォルダにある`calendar.blade.php`という名前のファイルを見つけてユーザーに表示するように伝えます。
*   **`[EventController::class, 'index']`**: これは少し違います。ビューを直接表示する代わりに、「`EventController`ファイルを見つけて、その中の`index`関数を実行せよ」と指示します。

### ステップ2：頭脳 - `app/Http/Controllers/EventController.php`

コントローラはロジックを処理するマネージャーです。私たちのカレンダーのJavaScriptは、このコントローラと対話してデータを取得・保存します。

新しいイベントを作成する`store`関数を見てみましょう。

```php
// /api/events へのPOSTリクエストが来ると 'store' 関数が呼ばれる
public function store(Request $request)
{
    // 1. データを検証する
    // 何かを保存する前に、データが正しいかチェックしなければなりません。
    $request->validate([
        'title' => 'required|string|max:255', // titleは必須、文字列、255文字未満
        'start' => 'required|date',           // startは必須、有効な日付
        'end' => 'required|date|after_or_equal:start', // endは必須、日付、開始日以降
    ]);

    // 2. イベントを作成する
    // 検証をパスしたら、データ専門家（Eventモデル）に新しいイベントを作成するように伝えます。
    return Event::create($request->all()); // 'all()'はカレンダーから送られた全データを取得します。
}
```

*   **`Request $request`**: これはブラウザから送信されたすべての情報（新しいイベントのタイトルや日付など）を保持するオブジェクトです。
*   **`$request->validate(...)`**: これは非常に便利なLaravelの機能です。データがルールに従っていない場合、Laravelは自動的に停止し、エラーメッセージを返します。
*   **`Event::create(...)`**: ここでコントローラがモデル��対話します。「ねえ、Eventモデル、このデータでデータベースに新しいエントリを作成してください」と伝えます。

### ステップ3：データ専門家 - `app/Models/Event.php`

モデルは、データベースのテーブルに直接接続された特別なクラスです。`Event`モデルは`events`テーブルに接続されています。これはLaravelの「Eloquent ORM」の一部で、データベースを簡単に扱うための素晴らしい方法です。

```php
class Event extends Model
{
    // これはセキュリティ機能です。ユーザー入力から保存を許可する
    // フィールドの「唯一の」リストです。
    protected $fillable = [
        'title',
        'start',
        'end',
    ];

    // これはLaravelに、'start'と'end'カラムを常に
    // 日付/時刻オブジェクトとして扱うように伝えます。これは非常に便利です。
    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];
}
```

複雑なSQLコードを書く必要はありません！`Event`モデルに何をすべきか（`create`、`find`、`update`など）を伝えるだけで、Laravelがデータベースとの通信を処理してくれます。

### ステップ4：設計図 - `database/migrations/..._create_events_table.php`

しかし、Laravelは`events`テーブルがどのように見えるべきか、どうやって知るのでしょうか？それは「マイグレーション」ファイルを使います。これはデータベースの設計図のようなものです。

```php
public function up(): void
{
    Schema::create('events', function (Blueprint $table) {
        $table->id(); // 各イベントにユニークな番号を作成します（例：1, 2, 3...）
        $table->string('title'); // テキスト用の 'title' カラムを作成します。
        $table->dateTime('start'); // 日付と時刻用の 'start' カラムを作成します。
        $table->dateTime('end');   // 日付と時刻用の 'end' カラムを作成します。
        $table->timestamps(); // 'created_at' と 'updated_at' カラムを自動的に作成します。
    });
}
```

`php artisan migrate`を実行すると、Laravelはこの設計図を読み取り、データベースに実際のテーブルを構築します。これは、チームの誰もが同じコマンドを実行して、全く同じデータベース構造を持つことができるので素晴らしいです。

### ステップ5：顔 - `resources/views/calendar.blade.php`

最後に、ビューはユーザーが見るものです。ほとんどがHTMLですが、Laravelが提供する特別な「Blade」構文がいくつか含まれています。

```html
<head>
    <!-- これは攻撃から保護するための特別なトークンです。Laravelが自動的に処理します。 -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- カレンダー用のJavaScriptライブラリを読み込みます -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
</head>
<body>
    <!-- ここにカレンダーが表示されます -->
    <div id="calendar"></div>

    <script>
        // このコードはページが読み込まれた後に実行されます
        document.addEventListener('DOMContentLoaded', async function() {
            // ... カレンダーの設定 ...

            // これがカレンダーのJavaScriptがLaravelバックエンドと対話する方法です。
            // 日付範囲を選択してイベントを作成すると...
            select: async (info) => {
                // ... タイトルを尋ねるプロンプトが表示され ...
                // そして、データを私たちのAPIルートに送信し���す！
                const response = await fetch('/api/events', {
                    method: 'POST', // web.phpの`Route::post`と一致します
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ title, start: info.startStr, end: info.endStr })
                });
                // ... そして、画面上のカレンダーにイベントを追加します。
            },
        });
    </script>
</body>
```

*   **`{{ csrf_token() }}`**: これはBlade構文です。`{{ }}`は、Laravelに中のPHPコードを実行して結果を表示するように伝えます。これはセキュリティ機能です。
*   **`fetch('/api/events', ...)`**: これが重要な部分です！ビューのJavaScriptが`/api/events` URLにリクエストを送信します。Laravelルーター（`web.php`）がこれを見て、`EventController`に送り、それが`Event`モデルを使ってデータを保存します。その間、あなたはカレンダーを見ています。

---

## フローのまとめ

1.  **ブラウザ:** `http://your-app.com/`を要求します。
2.  **ルーター (`web.php`):** `/`リクエストを見て、「`calendar`ビューを表示せよ」と言います。
3.  **ビュー (`calendar.blade.php`):** ページが読み込まれます。その中のJavaScriptがすぐに`/api/events`に`fetch`リクエストを送信し、すべてのイベントデータを取得します。
4.  **ルーター (`web.php`):** `/api/events`リクエストを見て、「`EventController`の`index`関数を実行せよ」と言います。
5.  **コントローラ (`EventController.php`):** `index`関数が実行されます。`Event`モデルにすべてのイベントを要求します。
6.  **モデル (`Event.php`):** データベースからすべてのイベントを取得し、コントローラに返します。
7.  **コントローラ:** イベントのリストをJSONデータとしてブラウザに返します。
8.  **ブラウザ:** ビューのJavaScriptがイベントのリストを受け取り、カレンダーに表示します。

この超詳細ガイドが、Laravelの魔法を理解する助けになることを願っています！
