<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';
    protected $description = 'Process upcoming event reminders and send email/browser alerts';

    public function handle()
    {
        $now = Carbon::now();
        $events = Event::where('reminder_sent', false)
            ->whereNotNull('reminder_minutes')
            ->where('start_time', '>', $now)
            ->get();

        $count = 0;
        foreach ($events as $event) {
            $reminderTime = (clone $event->start_time)->subMinutes($event->reminder_minutes);
            
            if ($now->greaterThanOrEqualTo($reminderTime)) {
                // Log and mark reminder sent
                Log::info("Event Reminder Dispatch: Event #{$event->id} - '{$event->title}' starts at {$event->start_time->toDateTimeString()}");
                $event->update(['reminder_sent' => true]);
                $count++;
            }
        }

        $this->info("Successfully processed {$count} event reminders.");
        return Command::SUCCESS;
    }
}
