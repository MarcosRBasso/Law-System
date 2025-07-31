<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'oab_number',
        'avatar',
        'phone',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'oab_number', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function createdClients()
    {
        return $this->hasMany(Client::class, 'created_by');
    }

    public function responsibleLawsuits()
    {
        return $this->hasMany(Lawsuit::class, 'responsible_lawyer_id');
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function clientInteractions()
    {
        return $this->hasMany(ClientInteraction::class);
    }

    public function createdDocuments()
    {
        return $this->hasMany(Document::class, 'created_by');
    }

    public function createdInvoices()
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    public function assignedEvents()
    {
        return $this->hasMany(CalendarEvent::class, 'assigned_to');
    }

    public function createdEvents()
    {
        return $this->hasMany(CalendarEvent::class, 'created_by');
    }

    public function createdDeadlines()
    {
        return $this->hasMany(Deadline::class, 'created_by');
    }

    public function completedDeadlines()
    {
        return $this->hasMany(Deadline::class, 'completed_by');
    }

    public function createdTransactions()
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLawyers($query)
    {
        return $query->whereNotNull('oab_number');
    }

    // Accessors & Mutators
    public function getIsLawyerAttribute()
    {
        return !is_null($this->oab_number);
    }
}