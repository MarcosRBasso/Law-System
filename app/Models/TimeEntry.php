<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lawsuit_id',
        'client_id',
        'description',
        'start_time',
        'end_time',
        'duration_minutes',
        'hourly_rate',
        'total_amount',
        'is_billable',
        'is_billed',
        'date',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'date' => 'date',
        'duration_minutes' => 'integer',
        'hourly_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_billable' => 'boolean',
        'is_billed' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lawsuit()
    {
        return $this->belongsTo(Lawsuit::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoiceItem()
    {
        return $this->hasOne(InvoiceItem::class);
    }

    // Scopes
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    public function scopeBilled($query)
    {
        return $query->where('is_billed', true);
    }

    public function scopeUnbilled($query)
    {
        return $query->where('is_billable', true)
                    ->where('is_billed', false);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('date', now()->month)
                    ->whereYear('date', now()->year);
    }

    public function scopeRunning($query)
    {
        return $query->whereNull('end_time');
    }

    // Accessors & Mutators
    public function getDurationInHoursAttribute()
    {
        return round($this->duration_minutes / 60, 2);
    }

    public function getIsRunningAttribute()
    {
        return is_null($this->end_time);
    }

    // Methods
    public function calculateDuration()
    {
        if ($this->start_time && $this->end_time) {
            $this->duration_minutes = $this->start_time->diffInMinutes($this->end_time);
            $this->total_amount = ($this->duration_minutes / 60) * $this->hourly_rate;
        }
    }

    public function stop()
    {
        if ($this->is_running) {
            $this->end_time = now();
            $this->calculateDuration();
            $this->save();
        }
    }
}