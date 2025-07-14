<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    // イベントを全件取得
    public function index()
    {
        return Event::all();
    }

    // イベントを登録
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        return Event::create($request->all());
    }

    // イベントを更新 (ドラッグ＆ドロップでの日付変更)
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