<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LawsuitParty extends Model
{
    use HasFactory;

    protected $fillable = [
        'lawsuit_id',
        'client_id',
        'type',
    ];

    // Relationships
    public function lawsuit()
    {
        return $this->belongsTo(Lawsuit::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopePlaintiffs($query)
    {
        return $query->where('type', 'plaintiff');
    }

    public function scopeDefendants($query)
    {
        return $query->where('type', 'defendant');
    }

    public function scopeThirdParties($query)
    {
        return $query->where('type', 'third_party');
    }
}