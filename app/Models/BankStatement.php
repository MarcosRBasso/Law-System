<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankStatement extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'file_path',
        'statement_date',
        'processed_at',
        'total_transactions',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'processed_at' => 'datetime',
        'total_transactions' => 'integer',
    ];

    // Relationships
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    // Scopes
    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('processed_at');
    }

    // Accessors
    public function getIsProcessedAttribute()
    {
        return !is_null($this->processed_at);
    }

    // Methods
    public function markAsProcessed($totalTransactions = 0)
    {
        $this->processed_at = now();
        $this->total_transactions = $totalTransactions;
        $this->save();
    }
}