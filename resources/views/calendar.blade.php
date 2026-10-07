<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Event Calendar & Smart Scheduling Studio</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .calendar-box {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .table-box {
            background: white;
            padding: 25px;
            border-radius: 20px;
            margin-top: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .color-preview {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-block;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .badge-priority-High {
            background-color: #dc3545 !important;
        }

        .badge-priority-Medium {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }

        .badge-priority-Low {
            background-color: #198754 !important;
        }

        .fc-event {
            cursor: pointer;
            border-radius: 6px;
            padding: 2px 5px;
            font-weight: 500;
        }

        .fc-toolbar-title {
            font-weight: 700;
            color: #2c3e50;
        }
    </style>
</head>

<body>

    <div class="container py-4">

        <!-- Header Title & Notification Status -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark m-0"><i class="fa-regular fa-calendar-check text-primary me-2"></i>Laravel Event Calendar Studio</h2>
                <p class="text-muted small m-0">Smart Recurring Events, Conflict Radar & Analytics Engine</p>
            </div>
            <div class="d-flex gap-2">
                <button id="enableNotifBtn" class="btn btn-outline-primary btn-sm rounded-pill" onclick="requestNotificationPermission()">
                    <i class="fa-solid fa-bell me-1"></i> Enable Live Alerts
                </button>
            </div>
        </div>

        {{-- DASHBOARD STATS --}}
        <div class="row g-3 mb-4">
            <div class="col-md-2 col-6">
                <div class="stat-card border-start border-4 border-primary">
                    <div class="text-muted small fw-bold">TOTAL EVENTS</div>
                    <h3 class="fw-bold m-0 text-primary">{{ $totalEvents }}</h3>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card border-start border-4 border-info">
                    <div class="text-muted small fw-bold">TODAY'S SCHEDULE</div>
                    <h3 class="fw-bold m-0 text-info">{{ $todayEvents }}</h3>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card border-start border-4 border-warning">
                    <div class="text-muted small fw-bold">UPCOMING</div>
                    <h3 class="fw-bold m-0 text-warning">{{ $upcomingEvents }}</h3>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card border-start border-4 border-danger">
                    <div class="text-muted small fw-bold">HIGH PRIORITY 🔥</div>
                    <h3 class="fw-bold m-0 text-danger">{{ $highPriorityEvents }}</h3>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card border-start border-4 border-success">
                    <div class="text-muted small fw-bold">COMPLETED</div>
                    <h3 class="fw-bold m-0 text-success">{{ $completedEvents }}</h3>
                </div>
            </div>
        </div>

        {{-- CALENDAR CONTAINER --}}
        <div class="calendar-box mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div class="d-flex gap-2">
                    <button class="btn btn-primary rounded-pill px-3" onclick="openModal()">
                        <i class="fa fa-plus me-1"></i> Add Event
                    </button>
                </div>

                <!-- Multi-Format Analytics Exporter Studio -->
                <div class="btn-group">
                    <button class="btn btn-outline-success dropdown-toggle rounded-pill px-3" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-file-export me-1"></i> Exporter Studio
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li>
                            <a href="{{ route('events.export.csv', array_merge(request()->query(), ['format' => 'csv'])) }}" class="dropdown-item">
                                <i class="fa-solid fa-file-csv text-success me-2"></i> Export CSV
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('events.export.csv', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="dropdown-item">
                                <i class="fa-solid fa-file-excel text-success me-2"></i> Export Excel (XLSX)
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('events.export.csv', array_merge(request()->query(), ['format' => 'json'])) }}" target="_blank" class="dropdown-item">
                                <i class="fa-solid fa-code text-primary me-2"></i> Export JSON
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('events.export.csv', array_merge(request()->query(), ['format' => 'pdf'])) }}" target="_blank" class="dropdown-item">
                                <i class="fa-solid fa-file-pdf text-danger me-2"></i> Print / PDF Analytics Report
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a href="{{ route('events.export.csv', array_merge(request()->query(), ['format' => 'ics'])) }}" class="dropdown-item">
                                <i class="fa-solid fa-calendar-plus text-warning me-2"></i> iCal Calendar (.ics Sync)
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- FullCalendar Container -->
            <div id="calendar"></div>
        </div>

        {{-- FILTER FORM --}}
        <form method="GET" action="{{ route('calendar') }}" class="row g-2 mb-4">
            <div class="col-md-3">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control rounded-pill" placeholder="🔍 Search Title, Description...">
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select rounded-pill">
                    <option value="">All Statuses</option>
                    <option value="today" {{ request('status')=='today' ? 'selected' : '' }}>Today's Events</option>
                    <option value="upcoming" {{ request('status')=='upcoming' ? 'selected' : '' }}>Upcoming</option>
                    <option value="completed" {{ request('status')=='completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="category" class="form-select rounded-pill">
                    <option value="">All Categories</option>
                    <option value="Meeting" {{ request('category')=='Meeting'?'selected':'' }}>Meeting</option>
                    <option value="Office" {{ request('category')=='Office'?'selected':'' }}>Office</option>
                    <option value="Birthday" {{ request('category')=='Birthday'?'selected':'' }}>Birthday</option>
                    <option value="Holiday" {{ request('category')=='Holiday'?'selected':'' }}>Holiday</option>
                    <option value="Personal" {{ request('category')=='Personal'?'selected':'' }}>Personal</option>
                    <option value="Exam" {{ request('category')=='Exam'?'selected':'' }}>Exam</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="priority" class="form-select rounded-pill">
                    <option value="">All Priorities</option>
                    <option value="High" {{ request('priority')=='High'?'selected':'' }}>🔥 High Priority</option>
                    <option value="Medium" {{ request('priority')=='Medium'?'selected':'' }}>⚡ Medium Priority</option>
                    <option value="Low" {{ request('priority')=='Low'?'selected':'' }}>🟢 Low Priority</option>
                </select>
            </div>

            <div class="col-md-1">
                <button class="btn btn-success rounded-pill w-100"><i class="fa fa-filter me-1"></i>Filter</button>
            </div>
            <div class="col-md-2 col-lg-1">
                <a href="{{ route('calendar') }}" class="btn btn-secondary rounded-pill w-100">Reset</a>
            </div>
        </form>

        {{-- BULK MULTI-SELECT & BATCH ACTIONS EVENT TABLE --}}
        <div class="table-box">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <h5 class="fw-bold m-0"><i class="fa-solid fa-list-check me-2 text-primary"></i>Event Registry & Bulk Operations</h5>

                <!-- Bulk Batch Actions Toolbar -->
                <div class="d-flex gap-2 align-items-center" id="bulkToolbar" style="display: none !important;">
                    <span class="badge bg-primary rounded-pill px-3" id="selectedCount">0 selected</span>

                    <select id="bulkActionSelect" class="form-select form-select-sm rounded-pill" style="width: auto;">
                        <option value="">-- Batch Action --</option>
                        <option value="mark_completed">Mark as Completed</option>
                        <option value="change_priority_High">Set Priority: High 🔥</option>
                        <option value="change_priority_Low">Set Priority: Low 🟢</option>
                        <option value="delete">Delete Selected</option>
                    </select>

                    <button class="btn btn-sm btn-outline-dark rounded-pill" onclick="executeBulkAction()">Apply</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll" class="form-check-input" onclick="toggleSelectAll(this)"></th>
                            <th>ID</th>
                            <th>Title & Description</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Status & Series</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eventList as $event)
                        <tr>
                            <td><input type="checkbox" class="form-check-input event-checkbox" value="{{ $event->id }}" onclick="updateBulkState()"></td>
                            <td>#{{ $event->id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="color-preview me-2" style="background:{{ $event->color }}"></span>
                                    <div>
                                        <strong>{{ $event->title }}</strong>
                                        @if($event->description)
                                            <br><small class="text-muted">{{ Str::limit($event->description, 50) }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-info bg-opacity-10 text-info border border-info px-2 py-1">{{ $event->category }}</span></td>
                            <td>
                                <span class="badge badge-priority-{{ $event->priority }}">
                                    {{ $event->priority === 'High' ? '🔥 High' : ($event->priority === 'Low' ? '🟢 Low' : '⚡ Medium') }}
                                </span>
                            </td>
                            <td>{{ $event->start_time->format('d M Y h:i A') }}</td>
                            <td>{{ $event->end_time->format('d M Y h:i A') }}</td>
                            <td>
                                @if($event->status == 'Upcoming')
                                    <span class="badge bg-warning text-dark">Upcoming</span>
                                @elseif($event->status == 'Completed')
                                    <span class="badge bg-success">Completed</span>
                                @else
                                    <span class="badge bg-primary">{{ $event->status }}</span>
                                @endif

                                @if($event->is_recurring)
                                    <span class="badge bg-secondary ms-1"><i class="fa-solid fa-rotate me-1"></i>{{ ucfirst($event->recurrence_type) }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('events.export.ics.single', $event->id) }}" class="btn btn-sm btn-light border me-1" title="Download .ics Sync File">
                                    <i class="fa-solid fa-calendar-plus text-warning"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No events found matching criteria.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($eventList->lastPage() > 1)
            <nav class="mt-3">
                <ul class="pagination justify-content-center">
                    @for ($i = 1; $i <= $eventList->lastPage(); $i++)
                        <li class="page-item {{ $eventList->currentPage() == $i ? 'active' : '' }}">
                            <a class="page-link" href="{{ $eventList->url($i) }}">{{ $i }}</a>
                        </li>
                    @endfor
                </ul>
            </nav>
            @endif
        </div>
    </div>

    {{-- SMART EVENT MODAL --}}
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">

                <div class="modal-header bg-light rounded-top-4">
                    <h5 class="modal-title fw-bold" id="modalTitle"><i class="fa-regular fa-calendar-plus me-2 text-primary"></i>Add Event</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <form id="eventForm">
                        @csrf
                        <input type="hidden" id="eventId">

                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Event Title</label>
                                <input type="text" id="title" class="form-control" placeholder="Meeting / Project Sprint / Birthday">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Category</label>
                                <select id="category" class="form-select">
                                    <option value="Meeting">Meeting</option>
                                    <option value="Office">Office</option>
                                    <option value="Birthday">Birthday</option>
                                    <option value="Holiday">Holiday</option>
                                    <option value="Personal">Personal</option>
                                    <option value="Exam">Exam</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea id="description" class="form-control" rows="2" placeholder="Agenda or notes..."></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Start Time</label>
                                <input type="datetime-local" id="start_time" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">End Time</label>
                                <input type="datetime-local" id="end_time" class="form-control">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Priority Radar</label>
                                <select id="priority" class="form-select">
                                    <option value="Medium">⚡ Medium Priority</option>
                                    <option value="High">🔥 High Priority</option>
                                    <option value="Low">🟢 Low Priority</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Reminder Alert</label>
                                <select id="reminder_minutes" class="form-select">
                                    <option value="">No Reminder</option>
                                    <option value="15">15 Minutes Before</option>
                                    <option value="60">1 Hour Before</option>
                                    <option value="1440">1 Day Before</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Calendar Color</label>
                                <div class="d-flex align-items-center gap-2">
                                    <span id="colorPreview" class="color-preview"></span>
                                    <input type="color" id="color" value="#3490dc" class="form-control form-control-color w-100">
                                </div>
                            </div>
                        </div>

                        <!-- Smart Recurring Rules Section -->
                        <div class="card bg-light border-0 p-3 mb-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="is_recurring" onchange="toggleRecurringOptions()">
                                <label class="form-check-label fw-bold text-dark" for="is_recurring">
                                    🔁 Enable Recurring Series (Auto-Schedule)
                                </label>
                            </div>

                            <div class="row g-2" id="recurringFields" style="display: none;">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Repeat Frequency</label>
                                    <select id="recurrence_type" class="form-select form-select-sm">
                                        <option value="daily">Daily (Every Day)</option>
                                        <option value="weekly" selected>Weekly (Every Week)</option>
                                        <option value="monthly">Monthly (Every Month)</option>
                                        <option value="yearly">Yearly (Every Year)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Number of Occurrences</label>
                                    <input type="number" id="recurrence_count" class="form-control form-control-sm" value="4" min="1" max="52">
                                </div>
                            </div>
                        </div>

                    </form>
                </div>

                <div class="modal-footer bg-light rounded-bottom-4">
                    <a id="icsDownloadSingle" href="#" target="_blank" class="btn btn-outline-warning btn-sm me-auto" style="display: none;">
                        <i class="fa-solid fa-calendar-plus me-1"></i> Download .ics Sync
                    </a>

                    <button class="btn btn-danger" id="deleteBtn">Delete Event</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary px-4" id="saveBtn"><i class="fa-solid fa-check me-1"></i> Save Event</button>
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

        function toggleRecurringOptions() {
            const isRec = document.getElementById('is_recurring').checked;
            document.getElementById('recurringFields').style.display = isRec ? 'flex' : 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                editable: true, // Enables Drag-and-Drop Rescheduling!
                selectable: true,

                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' // Multi-View Layout Switcher!
                },

                events: '/events',

                select: function(info) {
                    openModal();
                    document.getElementById('start_time').value = info.start.toISOString().slice(0, 16);
                    document.getElementById('end_time').value = info.end ? info.end.toISOString().slice(0, 16) : info.start.toISOString().slice(0, 16);
                },

                // Drag and Drop Rescheduler & Conflict Radar
                eventDrop: function(info) {
                    handleEventReschedule(info);
                },
                eventResize: function(info) {
                    handleEventReschedule(info);
                },

                eventClick: function(info) {
                    currentEventId = info.event.id;
                    document.getElementById('modalTitle').innerHTML = "<i class='fa-solid fa-pen text-primary me-2'></i>Edit Event";
                    document.getElementById('deleteBtn').style.display = "inline-block";
                    
                    const icsBtn = document.getElementById('icsDownloadSingle');
                    icsBtn.style.display = "inline-block";
                    icsBtn.href = "/events/export/ics/" + currentEventId;

                    document.getElementById('title').value = info.event.extendedProps.raw_title || info.event.title;
                    document.getElementById('description').value = info.event.extendedProps.description || "";
                    document.getElementById('category').value = info.event.extendedProps.category || "Meeting";
                    document.getElementById('priority').value = info.event.extendedProps.priority || "Medium";
                    document.getElementById('reminder_minutes').value = info.event.extendedProps.reminder_minutes || "";

                    document.getElementById('start_time').value = info.event.start ? info.event.start.toISOString().slice(0, 16) : "";
                    document.getElementById('end_time').value = info.event.end ? info.event.end.toISOString().slice(0, 16) : (info.event.start ? info.event.start.toISOString().slice(0, 16) : "");

                    document.getElementById('color').value = info.event.backgroundColor || "#3490dc";
                    document.getElementById('colorPreview').style.background = info.event.backgroundColor || "#3490dc";

                    document.getElementById('is_recurring').checked = !!info.event.extendedProps.is_recurring;
                    document.getElementById('recurrence_type').value = info.event.extendedProps.recurrence_type || "weekly";
                    document.getElementById('recurrence_count').value = info.event.extendedProps.recurrence_count || 4;
                    toggleRecurringOptions();

                    modal.show();
                }
            });

            calendar.render();

            // Handle Drag & Drop / Resize Rescheduling
            function handleEventReschedule(info) {
                let data = {
                    title: info.event.extendedProps.raw_title || info.event.title,
                    category: info.event.extendedProps.category || "Meeting",
                    start_time: info.event.start.toISOString().slice(0, 16),
                    end_time: info.event.end ? info.event.end.toISOString().slice(0, 16) : info.event.start.toISOString().slice(0, 16),
                    description: info.event.extendedProps.description || "",
                    priority: info.event.extendedProps.priority || "Medium",
                };

                fetch("/events/" + info.event.id, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        "Accept": "application/json"
                    },
                    body: JSON.stringify(data)
                })
                .then(async response => {
                    const res = await response.json();
                    if (!response.ok) {
                        alert("⚠️ " + (res.message || "Conflict detected!"));
                        info.revert();
                        throw new Error();
                    }
                    return res;
                })
                .then(res => {
                    alert("✅ Event rescheduled successfully!");
                    calendar.refetchEvents();
                })
                .catch(err => {
                    console.error(err);
                });
            }

            document.getElementById('color').addEventListener('input', function() {
                document.getElementById('colorPreview').style.background = this.value;
            });

            // Save Event
            document.getElementById('saveBtn').addEventListener('click', function() {
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
                    category: document.getElementById('category').value,
                    priority: document.getElementById('priority').value,
                    reminder_minutes: document.getElementById('reminder_minutes').value,
                    start_time: document.getElementById('start_time').value,
                    end_time: document.getElementById('end_time').value,
                    color: document.getElementById('color').value,
                    is_recurring: document.getElementById('is_recurring').checked,
                    recurrence_type: document.getElementById('recurrence_type').value,
                    recurrence_count: document.getElementById('recurrence_count').value,
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
                .then(async response => {
                    const res = await response.json();
                    if (!response.ok) {
                        alert("⚠️ " + (res.message || "Unable to save event. Check for conflicts."));
                        throw new Error();
                    }
                    return res;
                })
                .then(res => {
                    alert(res.message || "Event saved successfully!");
                    location.reload();
                })
                .catch(error => {
                    console.error(error);
                });
            });

            // Delete Event
            document.getElementById('deleteBtn').addEventListener('click', function() {
                if (!currentEventId) return;

                if (confirm("Are you sure you want to delete this event?")) {
                    fetch("/events/" + currentEventId, {
                        method: "DELETE",
                        headers: {
                            "X-CSRF-TOKEN": csrf,
                            "Accept": "application/json"
                        }
                    })
                    .then(async response => {
                        const res = await response.json();
                        if (!response.ok) {
                            alert(res.message || "Unable to delete event.");
                            throw new Error();
                        }
                        return res;
                    })
                    .then(data => {
                        alert("Event deleted successfully!");
                        location.reload();
                    })
                    .catch(error => {
                        console.error(error);
                    });
                }
            });

            // Poll active event reminders for Audio & Browser Push Notifications
            setInterval(checkActiveReminders, 30000);
            checkActiveReminders();
        });

        // Open Modal Reset
        function openModal() {
            currentEventId = null;
            document.getElementById('modalTitle').innerHTML = "<i class='fa-regular fa-calendar-plus me-2 text-primary'></i>Add Event";
            document.getElementById('eventForm').reset();
            document.getElementById('deleteBtn').style.display = "none";
            document.getElementById('icsDownloadSingle').style.display = "none";
            document.getElementById('color').value = "#3490dc";
            document.getElementById('colorPreview').style.background = "#3490dc";
            document.getElementById('category').value = "Meeting";
            document.getElementById('priority').value = "Medium";
            document.getElementById('is_recurring').checked = false;
            toggleRecurringOptions();
            modal.show();
        }

        // Bulk Checkbox Handling
        function toggleSelectAll(master) {
            document.querySelectorAll('.event-checkbox').forEach(cb => cb.checked = master.checked);
            updateBulkState();
        }

        function updateBulkState() {
            const selected = document.querySelectorAll('.event-checkbox:checked');
            const toolbar = document.getElementById('bulkToolbar');
            const selectedCount = document.getElementById('selectedCount');

            if (selected.length > 0) {
                toolbar.style.setProperty('display', 'flex', 'important');
                selectedCount.innerText = selected.length + " selected";
            } else {
                toolbar.style.setProperty('display', 'none', 'important');
            }
        }

        function executeBulkAction() {
            const actionVal = document.getElementById('bulkActionSelect').value;
            if (!actionVal) {
                alert("Please select a batch action.");
                return;
            }

            const ids = Array.from(document.querySelectorAll('.event-checkbox:checked')).map(cb => cb.value);
            if (ids.length === 0) return;

            let action = actionVal;
            let value = null;

            if (actionVal.startsWith('change_priority_')) {
                action = 'change_priority';
                value = actionVal.replace('change_priority_', '');
            }

            if (confirm("Execute batch action on " + ids.length + " events?")) {
                fetch("/events/bulk-action", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({ action: action, value: value, event_ids: ids })
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message || "Batch operation completed.");
                    location.reload();
                });
            }
        }

        // Notification & Audio Bell Alarm Engine
        function requestNotificationPermission() {
            if ("Notification" in window) {
                Notification.requestPermission().then(permission => {
                    if (permission === "granted") {
                        alert("🔔 Live Event Notification Alerts Enabled!");
                    }
                });
            }
        }

        function checkActiveReminders() {
            fetch('/events/reminders/active')
                .then(res => res.json())
                .then(events => {
                    events.forEach(event => {
                        if (event.minutes_left <= 30 && event.minutes_left >= 0) {
                            playAudioBell();
                            if ("Notification" in window && Notification.permission === "granted") {
                                new Notification("⏰ Event Starting Soon: " + event.title, {
                                    body: `Category: ${event.category} | Starts in ${event.minutes_left} mins at ${event.start_time_formatted}`,
                                    icon: 'https://cdn-icons-png.flaticon.com/512/3652/3652191.png'
                                });
                            }
                        }
                    });
                })
                .catch(err => console.error(err));
        }

        function playAudioBell() {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5 Note
                gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.5);
            } catch (e) {
                // Audio synthesis optional
            }
        }
    </script>
</body>

</html>