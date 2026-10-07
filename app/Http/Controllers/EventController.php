<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

class EventController extends Controller
{
    // Dashboard & Filters
    public function index(Request $request)
    {
        $query = Event::query();

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('category', 'like', '%' . $request->search . '%')
                    ->orWhere('priority', 'like', '%' . $request->search . '%');
            });
        }

        // Status Filter
        if ($request->filled('status')) {
            if ($request->status == 'today') {
                $query->whereDate('start_time', Carbon::today());
            } elseif ($request->status == 'upcoming') {
                $query->where('start_time', '>', now());
            } elseif ($request->status == 'completed') {
                $query->where('end_time', '<', now());
            }
        }

        // Category Filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Priority Filter
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $eventList = $query->orderBy('start_time', 'asc')->paginate(10);

        // Stats Counters
        $totalEvents = Event::count();
        $todayEvents = Event::whereDate('start_time', today())->count();
        $upcomingEvents = Event::where('start_time', '>', now())->count();
        $completedEvents = Event::where('end_time', '<', now())->count();
        $highPriorityEvents = Event::where('priority', 'High')->count();

        return view('calendar', compact(
            'eventList',
            'totalEvents',
            'todayEvents',
            'upcomingEvents',
            'completedEvents',
            'highPriorityEvents'
        ));
    }

    // Calendar JSON Data Endpoint for FullCalendar
    public function getEvents(Request $request)
    {
        $events = Event::all()->map(function ($event) {
            $titlePrefix = '[' . $event->category . '] ';
            if ($event->priority === 'High') {
                $titlePrefix = '🔥 ' . $titlePrefix;
            } elseif ($event->priority === 'Low') {
                $titlePrefix = '🟢 ' . $titlePrefix;
            }

            return [
                'id' => $event->id,
                'title' => $titlePrefix . $event->title,
                'raw_title' => $event->title,
                'start' => $event->start_time->toIso8601String(),
                'end' => $event->end_time->toIso8601String(),
                'color' => $event->color,
                'textColor' => '#ffffff',
                'description' => $event->description,
                'category' => $event->category,
                'priority' => $event->priority,
                'is_recurring' => $event->is_recurring,
                'recurrence_type' => $event->recurrence_type,
                'recurrence_count' => $event->recurrence_count,
                'reminder_minutes' => $event->reminder_minutes,
                'status' => $event->status,
            ];
        });

        return response()->json($events);
    }

    // Store Event with Smart Recurrence Engine & Conflict Detection
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'title' => 'required|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'category' => 'required|string|max:50',
            'priority' => 'nullable|string|in:High,Medium,Low',
            'is_recurring' => 'nullable|boolean',
            'recurrence_type' => 'nullable|string|in:daily,weekly,monthly,yearly',
            'recurrence_count' => 'nullable|integer|min:1|max:52',
            'reminder_minutes' => 'nullable|integer',
        ])->validate();

        // 1. Conflict Detection: Check for overlapping events
        $conflict = $this->checkTimeOverlap($request->start_time, $request->end_time);
        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'Conflict Alert! Another event ("' . $conflict->title . '") is already scheduled between ' . 
                             Carbon::parse($conflict->start_time)->format('h:i A') . ' and ' . 
                             Carbon::parse($conflict->end_time)->format('h:i A') . '.'
            ], 422);
        }

        $status = now()->greaterThan($request->end_time) ? 'Completed' : 'Upcoming';
        $priority = $request->priority ?? 'Medium';
        $isRecurring = filter_var($request->is_recurring, FILTER_VALIDATE_BOOLEAN);

        // Save Primary Event
        $parentEvent = Event::create([
            'title' => $request->title,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'color' => $request->color ?? '#3490dc',
            'status' => $status,
            'category' => $request->category,
            'priority' => $priority,
            'is_recurring' => $isRecurring,
            'recurrence_type' => $isRecurring ? $request->recurrence_type : null,
            'recurrence_count' => $isRecurring ? ($request->recurrence_count ?? 1) : 1,
            'reminder_minutes' => $request->reminder_minutes,
        ]);

        // 2. Recurrence Engine: Generate child recurring series
        if ($isRecurring && $request->recurrence_type && $request->recurrence_count > 1) {
            $count = min((int)$request->recurrence_count, 52);
            $start = Carbon::parse($request->start_time);
            $end = Carbon::parse($request->end_time);

            for ($i = 1; $i < $count; $i++) {
                $nextStart = clone $start;
                $nextEnd = clone $end;

                switch ($request->recurrence_type) {
                    case 'daily':
                        $nextStart->addDays($i);
                        $nextEnd->addDays($i);
                        break;
                    case 'weekly':
                        $nextStart->addWeeks($i);
                        $nextEnd->addWeeks($i);
                        break;
                    case 'monthly':
                        $nextStart->addMonths($i);
                        $nextEnd->addMonths($i);
                        break;
                    case 'yearly':
                        $nextStart->addYears($i);
                        $nextEnd->addYears($i);
                        break;
                }

                $childStatus = now()->greaterThan($nextEnd) ? 'Completed' : 'Upcoming';

                Event::create([
                    'title' => $request->title . ' (#' . ($i + 1) . ')',
                    'description' => $request->description,
                    'start_time' => $nextStart,
                    'end_time' => $nextEnd,
                    'color' => $request->color ?? '#3490dc',
                    'status' => $childStatus,
                    'category' => $request->category,
                    'priority' => $priority,
                    'is_recurring' => true,
                    'recurrence_type' => $request->recurrence_type,
                    'recurrence_count' => $count,
                    'reminder_minutes' => $request->reminder_minutes,
                    'recurrence_parent_id' => $parentEvent->id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Event and recurring series created successfully!',
            'event' => $parentEvent
        ]);
    }

    // Update / Reschedule Event with Overlap Prevention
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'title' => 'required|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'category' => 'required|string|max:50',
            'priority' => 'nullable|string|in:High,Medium,Low',
        ])->validate();

        $event = Event::findOrFail($id);

        // Check for time overlap conflicts with other events
        $conflict = $this->checkTimeOverlap($request->start_time, $request->end_time, $id);
        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'Reschedule Conflict! Another event ("' . $conflict->title . '") occupies this time slot (' . 
                             Carbon::parse($conflict->start_time)->format('h:i A') . ' - ' . 
                             Carbon::parse($conflict->end_time)->format('h:i A') . ').'
            ], 422);
        }

        $status = now()->greaterThan($request->end_time) ? 'Completed' : 'Upcoming';

        $event->update([
            'title' => $request->title,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'color' => $request->color ?? $event->color,
            'status' => $status,
            'category' => $request->category,
            'priority' => $request->priority ?? $event->priority,
            'reminder_minutes' => $request->reminder_minutes ?? $event->reminder_minutes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully!',
            'event' => $event
        ]);
    }

    // Delete Event
    public function destroy(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        
        // Option to delete recurring series
        if ($request->query('delete_series') == 'true') {
            Event::where('recurrence_parent_id', $id)
                ->orWhere('id', $id)
                ->delete();
        } else {
            $event->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully.'
        ]);
    }

    // Bulk Multi-Select & Batch Actions
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:delete,change_category,change_priority,change_color,mark_completed',
            'event_ids' => 'required|array|min:1',
            'event_ids.*' => 'integer|exists:events,id',
            'value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $ids = $request->event_ids;
        $action = $request->action;
        $value = $request->value;

        if ($action === 'delete') {
            Event::whereIn('id', $ids)->delete();
            $msg = count($ids) . ' events deleted successfully.';
        } elseif ($action === 'change_category') {
            Event::whereIn('id', $ids)->update(['category' => $value]);
            $msg = 'Category updated for selected events.';
        } elseif ($action === 'change_priority') {
            Event::whereIn('id', $ids)->update(['priority' => $value]);
            $msg = 'Priority updated for selected events.';
        } elseif ($action === 'change_color') {
            Event::whereIn('id', $ids)->update(['color' => $value]);
            $msg = 'Color tag updated for selected events.';
        } elseif ($action === 'mark_completed') {
            Event::whereIn('id', $ids)->update(['status' => 'Completed']);
            $msg = 'Selected events marked as Completed.';
        }

        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }

    // Export Studio (CSV, JSON, PDF Print View, iCal .ics)
    public function exportCsv(Request $request)
    {
        $format = strtolower($request->query('format', 'csv'));
        $query = Event::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('category', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            if ($request->status == 'today') {
                $query->whereDate('start_time', Carbon::today());
            } elseif ($request->status == 'upcoming') {
                $query->where('start_time', '>', now());
            } elseif ($request->status == 'completed') {
                $query->where('end_time', '<', now());
            }
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $events = $query->orderBy('start_time')->get();

        // 1. JSON Export
        if ($format === 'json') {
            return response()->json($events);
        }

        // 2. iCal (.ics) Calendar Sync Export
        if ($format === 'ics') {
            return $this->generateIcsResponse($events, 'calendar_events.ics');
        }

        // 3. Printable HTML / PDF View
        if ($format === 'pdf') {
            return view('exports.events_pdf', compact('events'));
        }

        // 4. CSV / Excel
        $response = new StreamedResponse(function () use ($events) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID', 'Title', 'Category', 'Priority', 'Description', 
                'Start Time', 'End Time', 'Status', 'Is Recurring', 'Color'
            ]);

            foreach ($events as $event) {
                fputcsv($handle, [
                    $event->id,
                    $event->title,
                    $event->category,
                    $event->priority,
                    $event->description,
                    $event->start_time->format('Y-m-d H:i:s'),
                    $event->end_time->format('Y-m-d H:i:s'),
                    $event->status,
                    $event->is_recurring ? 'Yes (' . $event->recurrence_type . ')' : 'No',
                    $event->color
                ]);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename=events.csv');

        return $response;
    }

    // Export Single Event to .ics iCal file for Google Calendar / Outlook Sync
    public function exportIcsSingle($id)
    {
        $event = Event::findOrFail($id);
        return $this->generateIcsResponse(collect([$event]), Str::slug($event->title) . '.ics');
    }

    // Live Active Reminder Watchdog Endpoint for Audio Bell & Browser Alerts
    public function getActiveReminders()
    {
        $now = Carbon::now();
        $nextHorizon = (clone $now)->addHours(24);

        $events = Event::where('start_time', '>=', $now)
            ->where('start_time', '<=', $nextHorizon)
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function ($event) use ($now) {
                $minutesLeft = round($now->diffInMinutes($event->start_time, false));
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'category' => $event->category,
                    'priority' => $event->priority,
                    'start_time_formatted' => $event->start_time->format('h:i A (d M)'),
                    'minutes_left' => $minutesLeft,
                    'is_urgent' => $minutesLeft <= 30,
                    'reminder_minutes' => $event->reminder_minutes,
                ];
            });

        return response()->json($events);
    }

    // Helper: Check Time Overlap for Conflict Prevention
    private function checkTimeOverlap($startTime, $endTime, $ignoreId = null)
    {
        $query = Event::where(function ($q) use ($startTime, $endTime) {
            $q->whereBetween('start_time', [$startTime, $endTime])
                ->orWhereBetween('end_time', [$startTime, $endTime])
                ->orWhere(function ($sub) use ($startTime, $endTime) {
                    $sub->where('start_time', '<=', $startTime)
                        ->where('end_time', '>=', $endTime);
                });
        });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->first();
    }

    // Helper: Build standard iCal (.ics) string format
    private function generateIcsResponse($events, $filename)
    {
        $icsContent = "BEGIN:VCALENDAR\r\n";
        $icsContent .= "VERSION:2.0\r\n";
        $icsContent .= "PRODID:-//Laravel Event Calendar//EN\r\n";
        $icsContent .= "CALSCALE:GREGORIAN\r\n";

        foreach ($events as $e) {
            $dtStart = Carbon::parse($e->start_time)->format('Ymd\THis\Z');
            $dtEnd = Carbon::parse($e->end_time)->format('Ymd\THis\Z');
            $summary = str_replace(["\r", "\n"], ' ', $e->title);
            $description = str_replace(["\r", "\n"], ' ', $e->description ?? '');

            $icsContent .= "BEGIN:VEVENT\r\n";
            $icsContent .= "UID:event-{$e->id}-" . time() . "@laravelcalendar.local\r\n";
            $icsContent .= "DTSTAMP:" . date('Ymd\THis\Z') . "\r\n";
            $icsContent .= "DTSTART:{$dtStart}\r\n";
            $icsContent .= "DTEND:{$dtEnd}\r\n";
            $icsContent .= "SUMMARY:[{$e->category}] {$summary}\r\n";
            $icsContent .= "DESCRIPTION:{$description}\r\n";
            $icsContent .= "STATUS:CONFIRMED\r\n";
            $icsContent .= "END:VEVENT\r\n";
        }

        $icsContent .= "END:VCALENDAR\r\n";

        return response($icsContent, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
