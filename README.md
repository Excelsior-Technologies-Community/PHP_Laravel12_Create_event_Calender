# PHP_Laravel12_Create_event_Calender

A complete, feature-rich event calendar application built with Laravel 12 and FullCalendar.js. This application allows users to create, view, update, and delete events through an intuitive and interactive calendar interface.

---

## Features

* FullCalendar integration with month, week, and day views
* Complete CRUD operations for events
* Drag and drop event rescheduling
* Event resizing to adjust duration
* Custom color coding for events
* Responsive design for desktop and mobile
* Client-side and server-side validation
* AJAX-based operations without page reloads
* CSRF protection and secure request handling

---

## Prerequisites

* PHP 8.1 or higher
* Composer
* MySQL or compatible database
* Node.js and npm (optional, for frontend assets)

---

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/laravel-event-calendar.git
cd laravel-event-calendar
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Update the `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=event_calendar
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Run Migrations

```bash
php artisan migrate
```

### 5. Start Development Server

```bash
php artisan serve
```

Access the application at:

[http://localhost:8000](http://localhost:8000)

---

## Project Structure

```
laravel-event-calendar/
├── app/
│   ├── Http/Controllers/
│   │   └── EventController.php
│   └── Models/
│       └── Event.php
├── database/
│   └── migrations/
│       └── xxxx_create_events_table.php
├── resources/
│   └── views/
│       └── calendar.blade.php
├── routes/
│   └── web.php
└── public/
```

---

## API Endpoints

| Method | Endpoint     | Description               |
| ------ | ------------ | ------------------------- |
| GET    | /            | Display calendar view     |
| GET    | /events      | Fetch events for calendar |
| POST   | /events      | Create new event          |
| PUT    | /events/{id} | Update an existing event  |
| DELETE | /events/{id} | Delete an event           |

---

## Usage

### Creating an Event

1. Click the "Add New Event" button
2. Fill in the event details:

   * Title (required)
   * Description (optional)
   * Start Time (required)
   * End Time (required)
   * Color (optional)
3. Click "Save"

### Editing an Event

1. Click on an existing event
2. Update the event details
3. Click "Save"

### Deleting an Event

1. Click on the event
2. Click the "Delete" button
3. Confirm deletion

### Rescheduling Events

* Drag and drop events to change date or time
* Resize events to modify duration

---

## Calendar Views

The calendar supports the following views:

* Month View: Displays the entire month
* Week View: Displays one week with time slots
* Day View: Displays a single day in detail

Switch views using the calendar header controls.

---

## Customization

### Event Colors

Each event supports custom color selection using a hex color code (for example, `#3490dc`). Colors can be set while creating or editing an event.

### Calendar Appearance

Calendar styling can be customized in:

```
resources/views/calendar.blade.php
```

FullCalendar theming options are fully supported.

### Extend Functionality

Common enhancements include:

* User authentication
* Event categories
* Recurring events
* Email or notification reminders
* Export events (PDF, CSV, iCal)

---

## Database Schema

### Events Table

```sql
CREATE TABLE events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    color VARCHAR(7) DEFAULT '#3490dc',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

---

## Validation Rules

* Title: Required, string, maximum 255 characters
* Start Time: Required, valid date and time
* End Time: Required, valid date and time, must be after start time
* Color: Optional, valid hex color code

---

## Security Features

* CSRF protection on all write operations
* Server-side input validation
* SQL injection prevention using Eloquent ORM
* XSS protection through Blade templating

---

## Troubleshooting

### Events Not Displaying

* Check browser console for JavaScript errors
* Verify database connection
* Ensure `/events` endpoint returns valid JSON

### Drag and Drop Issues

* Confirm FullCalendar scripts are loaded correctly
* Check for JavaScript conflicts
* Verify correct event data structure

### Form Submission Errors

* Inspect network requests in browser developer tools
* Verify CSRF token is included
* Check Laravel logs for validation errors

### Debug Mode

Enable detailed error reporting by setting in `.env`:

```env
APP_DEBUG=true
```

---

## Contributing

1. Fork the repository
2. Create a new feature branch

```bash
git checkout -b feature-name
```

3. Commit your changes

```bash
git commit -m "Add feature"
```

4. Push to the branch

```bash
git push origin feature-name
```

5. Submit a pull request

---

## ScreenShot

<img width="1673" height="935" alt="image" src="https://github.com/user-attachments/assets/b0518f87-89b5-4c4c-83b9-7698fabf3293" />

<img width="1675" height="935" alt="image" src="https://github.com/user-attachments/assets/e79d0033-b36a-4b8a-b131-471944f58dce" />

---

Contribution Guidelines

Fork the repository

Create a new branch for your feature:

git checkout -b feature-name


Commit your changes:

git commit -m "Add new feature"


Push your branch:

git push origin feature-name


Open a Pull Request
