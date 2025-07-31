<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LawsuitMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'lawsuit_id',
        'movement_date',
        'description',
        'type',
        'source',
        'external_id',
    ];

    protected $casts = [
        'movement_date' => 'date',
    ];

    // Relationships
    public function lawsuit()
    {
        return $this->belongsTo(Lawsuit::class);
    }

    // Scopes
    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeManual($query)
    {
        return $query->where('source', 'manual');
    }

    public function scopeAutomatic($query)
    {
        return $query->whereIn('source', ['pje', 'eproc', 'saj']);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('movement_date', '>=', now()->subDays($days));
    }
}