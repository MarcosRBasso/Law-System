<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Client extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'type',
        'name',
        'document',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'birth_date',
        'profession',
        'marital_status',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'document', 'email', 'phone', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lawsuits()
    {
        return $this->hasMany(Lawsuit::class);
    }

    public function contacts()
    {
        return $this->hasMany(ClientContact::class);
    }

    public function interactions()
    {
        return $this->hasMany(ClientInteraction::class);
    }

    public function tags()
    {
        return $this->belongsToMany(ClientTag::class, 'client_tag_pivot', 'client_id', 'tag_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function calendarEvents()
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function lawsuitParties()
    {
        return $this->hasMany(LawsuitParty::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeIndividuals($query)
    {
        return $query->where('type', 'individual');
    }

    public function scopeCompanies($query)
    {
        return $query->where('type', 'company');
    }

    // Accessors & Mutators
    public function getFormattedDocumentAttribute()
    {
        if ($this->type === 'individual') {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $this->document);
        } else {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $this->document);
        }
    }

    public function getFullAddressAttribute()
    {
        return trim("{$this->address}, {$this->city} - {$this->state}, {$this->zip_code}");
    }
}