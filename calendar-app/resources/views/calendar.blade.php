<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWA Event Calendar</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#4A90E2">

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        #calendar { max-width: 1100px; margin: 40px auto; padding: 0 10px; }
    </style>
</head>
<body>
    <div id="calendar"></div>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const calendarEl = document.getElementById('calendar');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                events: '/api/events',
                editable: true,
                selectable: true,

                // --- Event Handlers ---

                // 日付範囲を選択してイベントを追加
                select: async (info) => {
                    const title = prompt('イベント名を入力してください:');
                    if (!title) return;

                    const response = await fetch('/api/events', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ title, start: info.startStr, end: info.endStr })
                    });
                    const newEvent = await response.json();
                    calendar.addEvent(newEvent);
                },

                // イベントをドラッグして日付を更新
                eventDrop: async (info) => {
                    const event = info.event;
                    await fetch(`/api/events/${event.id}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ start: event.startStr, end: event.endStr })
                    });
                },

                // イベントをクリックして削除
                eventClick: async (info) => {
                    if (!confirm(`'${info.event.title}' を削除しますか？`)) return;
                    
                    await fetch(`/api/events/${info.event.id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken }
                    });
                    info.event.remove();
                }
            });

            calendar.render();
        });
    </script>
    
    {{-- Service Workerの登録 --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js')
                    .then(reg => console.log('Service worker registered.', reg))
                    .catch(err => console.log('Service worker registration failed.', err));
            });
        }
    </script>
</body>
</html>