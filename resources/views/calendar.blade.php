<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Event Calendar</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
        }

        .calendar-box {
            background: white;
            padding: 20px;
            border-radius: 15px;
        }

        .table-box {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-top: 30px;
        }

        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 5px;
            display: inline-block;
        }
    </style>
</head>

<body>

    <div class="container py-4">

        <h2>Laravel Event Calendar</h2>

        {{-- DASHBOARD --}}
        <div class="row mb-3">
            <div class="col">Total: {{ $totalEvents }}</div>
            <div class="col">Today: {{ $todayEvents }}</div>
            <div class="col">Upcoming: {{ $upcomingEvents }}</div>
            <div class="col">Completed: {{ $completedEvents }}</div>
        </div>


        {{-- CALENDAR --}}
        <div class="calendar-box">

            <button class="btn btn-primary mb-3" onclick="openModal()">Add Event</button>

            <div id="calendar"></div>
        </div>

        <form method="GET" action="{{ route('calendar') }}" class="row mb-3">

            <div class="col-md-5">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                    placeholder="Search Title or Description">
            </div>

            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="today" {{ request('status') == 'today' ? 'selected' : '' }}>Today's Events</option>
                    <option value="upcoming" {{ request('status') == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>

            <div class="col-md-2">
                <button class="btn btn-success w-100">
                    Search
                </button>
            </div>

            <div class="col-md-2">
                <a href="{{ route('calendar') }}" class="btn btn-secondary w-100">
                    Reset
                </a>
            </div>

        </form>

        {{-- TABLE --}}
        <div class="table-box">
            <h4>Event List</h4>

            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                        <th>Color</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($eventList as $event)
                        <tr>
                            <td>{{ $event->id }}</td>
                            <td>{{ $event->title }}</td>
                            <td>{{ $event->description }}</td>
                            <td>{{ $event->start_time->format('d M Y h:i A') }}</td>
                            <td>{{ $event->end_time->format('d M Y h:i A') }}</td>
                            <td>
                                @if($event->status == 'Upcoming')
                                    <span class="badge bg-warning">Upcoming</span>
                                @elseif($event->status == 'Completed')
                                    <span class="badge bg-success">Completed</span>
                                @else
                                    <span class="badge bg-primary">{{ $event->status }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="color-preview" style="background:{{ $event->color }}"></span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($eventList->lastPage() > 1)
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">

                        @for ($i = 1; $i <= $eventList->lastPage(); $i++)
                            <li class="page-item {{ $eventList->currentPage() == $i ? 'active' : '' }}">
                                <a class="page-link" href="{{ $eventList->url($i) }}">
                                    {{ $i }}
                                </a>
                            </li>
                        @endfor

                    </ul>
                </nav>
            @endif
        </div>

    </div>

    {{-- MODAL --}}
    <div class="modal fade" id="eventModal">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 id="modalTitle">Add Event</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <form id="eventForm">
                        @csrf
                        <input type="hidden" id="eventId">

                        <input type="text" id="title" class="form-control mb-2" placeholder="Title">

                        <textarea id="description" class="form-control mb-2" placeholder="Description"></textarea>

                        <input type="datetime-local" id="start_time" class="form-control mb-2">

                        <input type="datetime-local" id="end_time" class="form-control mb-2">

                        <div class="d-flex align-items-center mb-2">
                            <span id="colorPreview" class="color-preview me-2"></span>
                            <input type="color" id="color" value="#3490dc">
                        </div>

                    </form>

                </div>

                <div class="modal-footer">

                    <button class="btn btn-danger" id="deleteBtn">Delete</button>

                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                    <button class="btn btn-primary" id="saveBtn">Save</button>

                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>


    <script>
        let currentEventId = null;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const modal = new bootstrap.Modal(document.getElementById('eventModal'));

        document.getElementById('colorPreview').style.background = "#3490dc";

        document.addEventListener('DOMContentLoaded', function () {

            const calendarEl = document.getElementById('calendar');

            const calendar = new FullCalendar.Calendar(calendarEl, {

                initialView: 'dayGridMonth',

                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },

                events: '/events',

                selectable: true,

                editable: false,

                select: function (info) {

                    openModal();

                    document.getElementById('start_time').value =
                        info.start.toISOString().slice(0, 16);

                    document.getElementById('end_time').value =
                        info.end
                            ? info.end.toISOString().slice(0, 16)
                            : info.start.toISOString().slice(0, 16);

                },

                eventClick: function (info) {

                    currentEventId = info.event.id;

                    document.getElementById('modalTitle').innerHTML = "Edit Event";

                    document.getElementById('deleteBtn').style.display = "inline-block";

                    document.getElementById('title').value =
                        info.event.title;

                    document.getElementById('description').value =
                        info.event.extendedProps.description || "";

                    document.getElementById('start_time').value =
                        info.event.start
                            ? info.event.start.toISOString().slice(0, 16)
                            : "";

                    document.getElementById('end_time').value =
                        info.event.end
                            ? info.event.end.toISOString().slice(0, 16)
                            : info.event.start.toISOString().slice(0, 16);

                    document.getElementById('color').value =
                        info.event.backgroundColor;

                    document.getElementById('colorPreview').style.background =
                        info.event.backgroundColor;

                    modal.show();

                }

            });

            calendar.render();

            document.getElementById('color').addEventListener('input', function () {

                document.getElementById('colorPreview').style.background =
                    this.value;

            });

            document.getElementById('saveBtn').addEventListener('click', function () {

                if (document.getElementById('title').value.trim() == "") {
                    alert("Please enter event title");
                    return;
                }

                if (document.getElementById('start_time').value == "") {
                    alert("Please select start date");
                    return;
                }

                if (document.getElementById('end_time').value == "") {
                    alert("Please select end date");
                    return;
                }

                let data = {

                    title: document.getElementById('title').value,

                    description: document.getElementById('description').value,

                    start_time: document.getElementById('start_time').value,

                    end_time: document.getElementById('end_time').value,

                    color: document.getElementById('color').value

                };

                let url = "/events";
                let method = "POST";

                if (currentEventId) {

                    url = "/events/" + currentEventId;
                    method = "PUT";

                }

                fetch(url, {

                    method: method,

                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        "Accept": "application/json"
                    },

                    body: JSON.stringify(data)

                })

                    .then(response => {
                        if (!response.ok) {
                            throw new Error("Something went wrong");
                        }
                        return response.json();
                    })

                    .then(data => {

                        alert("Event saved successfully!");

                        location.reload();

                    })

                    .catch(error => {

                        console.log(error);

                    });

            });

            // Delete Event
            document.getElementById('deleteBtn').addEventListener('click', function () {

                if (!currentEventId) {
                    return;
                }

                if (confirm("Are you sure you want to delete this event?")) {

                    fetch("/events/" + currentEventId, {

                        method: "DELETE",

                        headers: {
                            "X-CSRF-TOKEN": csrf,
                            "Accept": "application/json"
                        }

                    })

                        .then(response => {
                            if (!response.ok) {
                                throw new Error("Something went wrong");
                            }
                            return response.json();
                        })

                        .then(data => {

                            alert("Event deleted successfully!");

                            location.reload();

                        })

                        .catch(error => {

                            console.log(error);

                        });

                }

            });

        }); // End DOMContentLoaded

        // Open Modal
        function openModal() {

            currentEventId = null;

            document.getElementById('modalTitle').innerHTML = "Add Event";

            document.getElementById('eventForm').reset();

            document.getElementById('deleteBtn').style.display = "none";

            document.getElementById('color').value = "#3490dc";

            document.getElementById('colorPreview').style.background = "#3490dc";

            modal.show();

        }
    </script>

</body>

</html>