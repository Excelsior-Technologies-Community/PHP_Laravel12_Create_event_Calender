<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Event Calendar</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .calendar-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #3490dc;
            color: white;
        }
        .btn-danger {
            background-color: #e3342f;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            display: inline-block;
            margin-right: 10px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="calendar-container">
        <h1>Event Calendar</h1>
        <button class="btn btn-primary" onclick="openModal()">Add New Event</button>
        <div id="calendar"></div>
    </div>

    <!-- Event Modal -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Create Event</h2>
            <form id="eventForm">
                @csrf
                <input type="hidden" id="eventId">
                
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="start_time">Start Time *</label>
                    <input type="datetime-local" id="start_time" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="end_time">End Time *</label>
                    <input type="datetime-local" id="end_time" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="color">Color</label>
                    <div>
                        <span class="color-preview" id="colorPreview"></span>
                        <input type="color" id="color" class="form-control" style="width: 100px; display: inline-block;">
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-danger" id="deleteBtn" onclick="deleteEvent()" style="display: none;">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            let currentEventId = null;

            // Initialize calendar
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: '/events',
                editable: true,
                selectable: true,
                select: function(info) {
                    openModal();
                    document.getElementById('start_time').value = info.startStr.substring(0, 16);
                    document.getElementById('end_time').value = info.endStr.substring(0, 16);
                },
                eventClick: function(info) {
                    currentEventId = info.event.id;
                    openModal('Edit Event');
                    
                    // Fill form with event data
                    document.getElementById('eventId').value = info.event.id;
                    document.getElementById('title').value = info.event.title;
                    document.getElementById('description').value = info.event.extendedProps.description || '';
                    document.getElementById('start_time').value = info.event.start.toISOString().substring(0, 16);
                    document.getElementById('end_time').value = info.event.end ? info.event.end.toISOString().substring(0, 16) : '';
                    document.getElementById('color').value = info.event.backgroundColor;
                    document.getElementById('colorPreview').style.backgroundColor = info.event.backgroundColor;
                    
                    // Show delete button
                    document.getElementById('deleteBtn').style.display = 'inline-block';
                },
                eventDrop: function(info) {
                    updateEvent(info.event);
                },
                eventResize: function(info) {
                    updateEvent(info.event);
                }
            });

            calendar.render();

            // Color preview
            document.getElementById('color').addEventListener('input', function(e) {
                document.getElementById('colorPreview').style.backgroundColor = e.target.value;
            });

            // Default color
            document.getElementById('colorPreview').style.backgroundColor = document.getElementById('color').value;

            // Form submission
            document.getElementById('eventForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const eventData = {
                    title: document.getElementById('title').value,
                    description: document.getElementById('description').value,
                    start_time: document.getElementById('start_time').value,
                    end_time: document.getElementById('end_time').value,
                    color: document.getElementById('color').value
                };

                if (currentEventId) {
                    // Update event
                    fetch(`/events/${currentEventId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(eventData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        calendar.refetchEvents();
                        closeModal();
                    })
                    .catch(error => console.error('Error:', error));
                } else {
                    // Create event
                    fetch('/events', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(eventData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        calendar.refetchEvents();
                        closeModal();
                    })
                    .catch(error => console.error('Error:', error));
                }
            });

            // Update event function
            function updateEvent(event) {
                const eventData = {
                    title: event.title,
                    start_time: event.start.toISOString(),
                    end_time: event.end ? event.end.toISOString() : event.start.toISOString(),
                    color: event.backgroundColor
                };

                fetch(`/events/${event.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(eventData)
                })
                .then(response => response.json())
                .catch(error => console.error('Error:', error));
            }

            // Modal functions
            window.openModal = function(title = 'Create Event') {
                document.getElementById('modalTitle').textContent = title;
                document.getElementById('eventModal').style.display = 'block';
            }

            window.closeModal = function() {
                document.getElementById('eventModal').style.display = 'none';
                document.getElementById('eventForm').reset();
                document.getElementById('eventId').value = '';
                document.getElementById('deleteBtn').style.display = 'none';
                currentEventId = null;
                document.getElementById('colorPreview').style.backgroundColor = document.getElementById('color').value;
            }

            window.deleteEvent = function() {
                if (!currentEventId) return;
                
                if (confirm('Are you sure you want to delete this event?')) {
                    fetch(`/events/${currentEventId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        calendar.refetchEvents();
                        closeModal();
                    })
                    .catch(error => console.error('Error:', error));
                }
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('eventModal');
                if (event.target === modal) {
                    closeModal();
                }
            }
        });
    </script>
</body>
</html>