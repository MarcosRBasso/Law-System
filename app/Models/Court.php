<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Court extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'city',
        'state',
        'api_endpoint',
    ];

    // Relationships
    public function lawsuits()
    {
        return $this->hasMany(Lawsuit::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByState($query, $state)
    {
        return $query->where('state', $state);
    }

    public function scopeWithApi($query)
    {
        return $query->whereNotNull('api_endpoint');
    }
}