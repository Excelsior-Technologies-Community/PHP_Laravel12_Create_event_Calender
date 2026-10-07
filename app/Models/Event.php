<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_time',
        'end_time',
        'color',
        'status',
        'category',
        'priority',
        'is_recurring',
        'recurrence_type',
        'recurrence_count',
        'reminder_minutes',
        'reminder_sent',
        'recurrence_parent_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_recurring' => 'boolean',
        'reminder_sent' => 'boolean',
        'recurrence_count' => 'integer',
        'reminder_minutes' => 'integer',
    ];

    public function parentEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'recurrence_parent_id');
    }

    public function childEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'recurrence_parent_id');
    }
}