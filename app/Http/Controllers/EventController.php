<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('category', 'like', '%' . $request->search . '%');
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

        if ($request->filled('category')) {

            $query->where('category', $request->category);
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

                'title' => '[' . $event->category . '] ' . $event->title,

                'start' => $event->start_time->toIso8601String(),

                'end' => $event->end_time->toIso8601String(),

                'color' => $event->color,

                'description' => $event->description,

                'category' => $event->category,

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

            'category' => 'required|string|max:50',

        ])->validate();

        $exists = Event::where(function ($query) use ($request) {

            $query->whereBetween('start_time', [

                $request->start_time,

                $request->end_time

            ])

                ->orWhereBetween('end_time', [

                    $request->start_time,

                    $request->end_time

                ])

                ->orWhere(function ($q) use ($request) {

                    $q->where('start_time', '<=', $request->start_time)
                        ->where('end_time', '>=', $request->end_time);
                });
        })->exists();

        if ($exists) {

            return response()->json([

                'success' => false,

                'message' => 'Another event already exists during this time.'

            ], 422);
        }

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

            'category' => $request->category,

        ]);

        return response()->json([

            'success' => true,

            'event' => $event

        ]);
    }

    // Update
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [

            'title' => 'required|max:255',

            'start_time' => 'required|date',

            'end_time' => 'required|date|after:start_time',

            'category' => 'required|string|max:50',

        ])->validate();

        $exists = Event::where('id', '!=', $id)

            ->where(function ($query) use ($request) {

                $query->whereBetween('start_time', [

                    $request->start_time,

                    $request->end_time

                ])

                    ->orWhereBetween('end_time', [

                        $request->start_time,

                        $request->end_time

                    ])

                    ->orWhere(function ($q) use ($request) {

                        $q->where('start_time', '<=', $request->start_time)
                            ->where('end_time', '>=', $request->end_time);
                    });
            })

            ->exists();

        if ($exists) {

            return response()->json([

                'success' => false,

                'message' => 'Another event already exists during this time.'

            ], 422);
        }

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

            'category' => $request->category,

        ]);

        return response()->json([

            'success' => true,

            'event' => $event

        ]);
    }

    // Delete
    public function destroy($id)
    {
        Event::findOrFail($id)->delete();

        return response()->json([
            'success' => true
        ]);
    }

    public function exportCsv(Request $request)
    {
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

        $events = $query->orderBy('start_time')->get();

        $response = new StreamedResponse(function () use ($events) {

            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Title',
                'Category',
                'Description',
                'Start Time',
                'End Time',
                'Status',
                'Color'
            ]);

            foreach ($events as $event) {

                fputcsv($handle, [

                    $event->id,

                    $event->title,

                    $event->category,

                    $event->description,

                    $event->start_time,

                    $event->end_time,

                    $event->status,

                    $event->color

                ]);
            }

            fclose($handle);
        });

        $response->headers->set(
            'Content-Type',
            'text/csv'
        );

        $response->headers->set(
            'Content-Disposition',
            'attachment; filename=events.csv'
        );

        return $response;
    }
}
