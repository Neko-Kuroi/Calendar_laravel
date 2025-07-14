# Laravel Calendar Application Tutorial for PHP Beginners

This tutorial will guide you through a simple calendar application built with the Laravel framework. We'll explore the key files and concepts to help you understand how a basic web application is structured in PHP.

## Application Overview

This application is a single-page calendar where you can create, update, and delete events. It uses the [FullCalendar](https://fullcalendar.io/) library for the frontend and a Laravel backend to manage the event data.

## Directory Structure

Let's start by looking at the main directories and files involved in this application:

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

*   `app/`: This directory contains the core code of your application, including Models and Controllers.
*   `resources/`: This directory holds your application's views (the HTML templates).
*   `routes/`: This directory contains all the URL definitions for your application.

## Understanding the Code

Now, let's dive into the specific files and understand how they work together.

### 1. `routes/web.php` - Defining the URLs

This file defines the URLs (or "routes") for your application. When you visit a URL in your browser, Laravel looks in this file to determine what to do.

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;

// The main view for the calendar display
Route::get('/', function () {
    return view('calendar');
});

// API Routes
Route::prefix('api')->group(function () {
    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
});
```

*   `Route::get('/', ...)`: This line defines a route for the homepage (`/`). When a user visits this URL, Laravel returns the `calendar` view.
*   `Route::prefix('api')->group(...)`: This groups a set of routes under the `/api` prefix. These are the routes that the FullCalendar library will use to interact with the backend.
    *   `GET /api/events`: Fetches all events from the database.
    *   `POST /api/events`: Creates a new event.
    *   `PUT /api/events/{event}`: Updates an existing event.
    *   `DELETE /api/events/{event}`: Deletes an event.

### 2. `app/Http/Controllers/EventController.php` - Handling Requests

This controller is responsible for handling the API requests we defined in `web.php`.

```php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    // Get all events
    public function index()
    {
        return Event::all();
    }

    // Create a new event
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        return Event::create($request->all());
    }

    // Update an event
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

    // Delete an event
    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json(['status' => 'success']);
    }
}
```

*   `index()`: This method retrieves all events from the database using the `Event` model and returns them as a JSON response.
*   `store(Request $request)`: This method creates a new event. It first validates the incoming request data to ensure that the `title`, `start`, and `end` fields are present and valid. Then, it creates a new `Event` in the database.
*   `update(Request $request, Event $event)`: This method updates an existing event. It validates the `start` and `end` dates and then updates the event in the database.
*   `destroy(Event $event)`: This method deletes an event from the database.

### 3. `app/Models/Event.php` - The Data Model

This file defines the `Event` model, which represents the `events` table in your database.

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

*   `$fillable`: This array specifies which fields are allowed to be mass-assigned. This is a security feature to prevent unwanted fields from being updated.
*   `$casts`: This array defines how certain attributes should be converted to common data types. In this case, the `start` and `end` fields are automatically converted to `datetime` objects.

### 4. `resources/views/calendar.blade.php` - The User Interface

This is the main view file that the user sees. It's a standard HTML file with some embedded JavaScript.

```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>PWA Event Calendar</title>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <style>
        /* ... CSS styles ... */
    </style>
</head>
<body>
    <div id="calendar"></div>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                // ... FullCalendar options ...
                events: '/api/events', // Load events from our API
                // ... event handlers for creating, updating, and deleting events ...
            });
            calendar.render();
        });
    </script>
</body>
</html>
```

*   The file includes the FullCalendar library from a CDN.
*   It has a `div` with the ID `calendar` where the calendar will be rendered.
*   The JavaScript code initializes FullCalendar and sets up event handlers for:
    *   **Selecting a date range:** This allows you to create new events.
    *   **Dragging and dropping an event:** This updates the event's date.
    *   **Clicking on an event:** This deletes the event.

These event handlers make `fetch` requests to the API routes we defined in `web.php`.

## How to Run the Application

To run this application on your local machine, you'll need to have PHP and Composer installed.

1.  **Clone the repository:**
    ```bash
    git clone <repository_url>
    cd calendar-app
    ```

2.  **Install dependencies:**
    ```bash
    composer install
    npm install
    ```

3.  **Set up the environment file:**
    ```bash
    cp .env.example .env
    ```
    You'll need to create a database and update the `.env` file with your database credentials.

4.  **Generate an application key:**
    ```bash
    php artisan key:generate
    ```

5.  **Run the database migrations:**
    ```bash
    php artisan migrate
    ```

6.  **Start the development server:**
    ```bash
    php artisan serve
    ```

Now you can open your browser and visit `http://127.0.0.1:8000` to see the calendar application in action.

We hope this tutorial has been helpful for understanding the basics of a Laravel application!
