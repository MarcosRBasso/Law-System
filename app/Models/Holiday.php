<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'is_national',
        'state',
        'city',
    ];

    protected $casts = [
        'date' => 'date',
        'is_national' => 'boolean',
    ];

    // Scopes
    public function scopeNational($query)
    {
        return $query->where('is_national', true);
    }

    public function scopeByState($query, $state)
    {
        return $query->where('state', $state);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    public function scopeInYear($query, $year)
    {
        return $query->whereYear('date', $year);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', today());
    }

    // Static methods
    public static function isHoliday($date, $state = null, $city = null)
    {
        $query = static::whereDate('date', $date);
        
        $query->where(function ($q) use ($state, $city) {
            $q->where('is_national', true);
            
            if ($state) {
                $q->orWhere('state', $state);
            }
            
            if ($city) {
                $q->orWhere('city', $city);
            }
        });
        
        return $query->exists();
    }
}