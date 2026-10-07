<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Analytics & Schedule Export Report</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fff; color: #333; margin: 30px; }
        .header { text-align: center; border-bottom: 2px solid #3490dc; padding-bottom: 15px; margin-bottom: 25px; }
        .header h2 { margin: 0; color: #1e3a8a; }
        .header p { margin: 5px 0 0 0; color: #666; font-size: 14px; }
        .stats-grid { display: flex; justify-content: space-around; margin-bottom: 30px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .stat-item { text-align: center; }
        .stat-num { font-size: 20px; font-weight: bold; color: #2563eb; }
        .stat-label { font-size: 12px; color: #64748b; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; font-size: 13px; }
        th { background: #f1f5f9; color: #1e293b; font-weight: 600; }
        tr:nth-child(even) { background: #f8fafc; }
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; color: #fff; }
        .badge-high { background: #ef4444; }
        .badge-medium { background: #f59e0b; }
        .badge-low { background: #10b981; }
        .badge-category { background: #0284c7; }
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 15px; }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h2>📅 Event Schedule & Analytics Report</h2>
        <p>Generated on {{ date('d M Y, h:i A') }} | Total Records: {{ $events->count() }}</p>
    </div>

    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-num">{{ $events->count() }}</div>
            <div class="stat-label">Total Events</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">{{ $events->where('priority', 'High')->count() }}</div>
            <div class="stat-label">High Priority</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">{{ $events->where('status', 'Upcoming')->count() }}</div>
            <div class="stat-label">Upcoming</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">{{ $events->where('status', 'Completed')->count() }}</div>
            <div class="stat-label">Completed</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
                <th>Recurring</th>
            </tr>
        </thead>
        <tbody>
            @foreach($events as $event)
            <tr>
                <td>#{{ $event->id }}</td>
                <td><strong>{{ $event->title }}</strong><br><small>{{ $event->description }}</small></td>
                <td><span class="badge badge-category">{{ $event->category }}</span></td>
                <td>
                    <span class="badge {{ $event->priority === 'High' ? 'badge-high' : ($event->priority === 'Low' ? 'badge-low' : 'badge-medium') }}">
                        {{ $event->priority }}
                    </span>
                </td>
                <td>{{ $event->start_time->format('d M Y, h:i A') }}</td>
                <td>{{ $event->end_time->format('d M Y, h:i A') }}</td>
                <td>{{ $event->status }}</td>
                <td>{{ $event->is_recurring ? 'Yes (' . ucfirst($event->recurrence_type) . ')' : 'No' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Laravel Event Calendar Analytics System &bull; Auto-generated Report
    </div>

</body>
</html>
