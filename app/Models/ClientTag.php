<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
    ];

    // Relationships
    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_tag_pivot', 'tag_id', 'client_id');
    }
}