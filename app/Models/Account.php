<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'bank_code',
        'agency',
        'account_number',
        'initial_balance',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function bankStatements()
    {
        return $this->hasMany(BankStatement::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Methods
    public function updateBalance()
    {
        $income = $this->transactions()->where('type', 'income')->sum('amount');
        $expenses = $this->transactions()->where('type', 'expense')->sum('amount');
        
        $this->current_balance = $this->initial_balance + $income - $expenses;
        $this->save();
    }

    public function getFormattedAccountNumberAttribute()
    {
        if ($this->agency && $this->account_number) {
            return "{$this->agency}/{$this->account_number}";
        }
        return $this->account_number;
    }
}