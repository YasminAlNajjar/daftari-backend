<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'report_export_id',
        'verification_token_hash',
        'file_hash',
    ];

    /**
     * The user who owns this report certificate.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The exported report associated with this certificate.
     */
    public function reportExport(): BelongsTo
    {
        return $this->belongsTo(ReportExport::class);
    }
}