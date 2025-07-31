<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deadline extends Model
{
    use HasFactory;

    protected $fillable = [
        'lawsuit_id',
        'title',
        'description',
        'due_date',
        'alert_days_before',
        'status',
        'completed_at',
        'completed_by',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'alert_days_before' => 'integer',
    ];

    // Relationships
    public function lawsuit()
    {
        return $this->belongsTo(Lawsuit::class);
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
                    ->orWhere(function ($q) {
                        $q->where('status', 'pending')
                          ->where('due_date', '<', now());
                    });
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today())
                    ->where('status', 'pending');
    }

    public function scopeDueSoon($query, $days = 3)
    {
        return $query->whereBetween('due_date', [today(), today()->addDays($days)])
                    ->where('status', 'pending');
    }

    // Accessors & Mutators
    public function getIsOverdueAttribute()
    {
        return $this->due_date->isPast() && $this->status === 'pending';
    }

    public function getDaysUntilDueAttribute()
    {
        return today()->diffInDays($this->due_date, false);
    }

    public function getAlertDateAttribute()
    {
        return $this->due_date->subDays($this->alert_days_before);
    }
}