<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class EventController extends Controller
{
    // Dashboard
    public function index(Request $request)
    {
        $query = Event::query();

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Status Filter
        if ($request->filled('status')) {

            if ($request->status == 'today') {
                $query->whereDate('start_time', Carbon::today());
            }

            elseif ($request->status == 'upcoming') {
                $query->where('start_time', '>', now());
            }

            elseif ($request->status == 'completed') {
                $query->where('end_time', '<', now());
            }
        }

        $eventList = $query->oldest()->paginate(5);

        $totalEvents = Event::count();

        $todayEvents = Event::whereDate('start_time', today())->count();

        $upcomingEvents = Event::where('start_time', '>', now())->count();

        $completedEvents = Event::where('end_time', '<', now())->count();

        return view('calendar', compact(
            'eventList',
            'totalEvents',
            'todayEvents',
            'upcomingEvents',
            'completedEvents'
        ));
    }

    // Calendar Events
    public function getEvents(Request $request)
    {
        $events = Event::all()->map(function ($event) {

            return [

                'id' => $event->id,

                'title' => $event->title,

                'start' => $event->start_time->toIso8601String(),

                'end' => $event->end_time->toIso8601String(),

                'color' => $event->color,

                'description' => $event->description,
            ];
        });

        return response()->json($events);
    }

    // Store
    public function store(Request $request)
    {
        Validator::make($request->all(), [

            'title' => 'required|max:255',

            'start_time' => 'required|date',

            'end_time' => 'required|date|after:start_time',

        ])->validate();

        $status = now()->greaterThan($request->end_time)
            ? 'Completed'
            : 'Upcoming';

        $event = Event::create([

            'title' => $request->title,

            'description' => $request->description,

            'start_time' => $request->start_time,

            'end_time' => $request->end_time,

            'color' => $request->color ?? '#3490dc',

            'status' => $status,

        ]);

        return response()->json($event);
    }

    // Update
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [

            'title' => 'required|max:255',

            'start_time' => 'required|date',

            'end_time' => 'required|date|after:start_time',

        ])->validate();

        $event = Event::findOrFail($id);

        $status = now()->greaterThan($request->end_time)
            ? 'Completed'
            : 'Upcoming';

        $event->update([

            'title' => $request->title,

            'description' => $request->description,

            'start_time' => $request->start_time,

            'end_time' => $request->end_time,

            'color' => $request->color,

            'status' => $status,

        ]);

        return response()->json($event);
    }

    // Delete
    public function destroy($id)
    {
        Event::findOrFail($id)->delete();

        return response()->json([
            'success' => true
        ]);
    }
}