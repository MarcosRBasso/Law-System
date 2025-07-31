<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'signer_name',
        'signer_email',
        'signer_document',
        'signature_date',
        'certificate_info',
        'signature_status',
    ];

    protected $casts = [
        'signature_date' => 'datetime',
        'certificate_info' => 'array',
    ];

    // Relationships
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('signature_status', 'pending');
    }

    public function scopeSigned($query)
    {
        return $query->where('signature_status', 'signed');
    }

    public function scopeRejected($query)
    {
        return $query->where('signature_status', 'rejected');
    }
}