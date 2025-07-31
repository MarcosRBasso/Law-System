<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Lawsuit extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'number',
        'client_id',
        'responsible_lawyer_id',
        'court_id',
        'subject',
        'description',
        'value',
        'status',
        'phase',
        'instance',
        'distribution_date',
        'estimated_end_date',
        'actual_end_date',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'distribution_date' => 'date',
        'estimated_end_date' => 'date',
        'actual_end_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'status', 'phase', 'responsible_lawyer_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function responsibleLawyer()
    {
        return $this->belongsTo(User::class, 'responsible_lawyer_id');
    }

    public function court()
    {
        return $this->belongsTo(Court::class);
    }

    public function movements()
    {
        return $this->hasMany(LawsuitMovement::class);
    }

    public function parties()
    {
        return $this->hasMany(LawsuitParty::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function calendarEvents()
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function deadlines()
    {
        return $this->hasMany(Deadline::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPhase($query, $phase)
    {
        return $query->where('phase', $phase);
    }

    public function scopeByInstance($query, $instance)
    {
        return $query->where('instance', $instance);
    }

    public function scopeByLawyer($query, $lawyerId)
    {
        return $query->where('responsible_lawyer_id', $lawyerId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('estimated_end_date', '<', now())
                    ->whereIn('status', ['active', 'suspended']);
    }

    // Accessors & Mutators
    public function getFormattedNumberAttribute()
    {
        return preg_replace('/(\d{7})(\d{2})(\d{4})(\d{1})(\d{2})(\d{4})/', '$1-$2.$3.$4.$5.$6', $this->number);
    }

    public function getIsOverdueAttribute()
    {
        return $this->estimated_end_date && 
               $this->estimated_end_date->isPast() && 
               in_array($this->status, ['active', 'suspended']);
    }

    public function getDurationInDaysAttribute()
    {
        $endDate = $this->actual_end_date ?: now();
        return $this->distribution_date->diffInDays($endDate);
    }
}