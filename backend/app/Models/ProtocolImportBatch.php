<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProtocolImportBatch extends Model
{
    use HasFactory;

    public const STATUS_PARSED = 'parsed';
    public const STATUS_REVIEW_NEEDED = 'review_needed';
    public const STATUS_APPLIED = 'applied';

    protected $fillable = [
        'batch_uuid',
        'source_filename',
        'source_extension',
        'uploaded_by_admin_id',
        'uploaded_by_admin_name',
        'applied_by_admin_id',
        'applied_by_admin_name',
        'status',
        'total_rows',
        'saved_rows',
        'created_rows',
        'updated_rows',
        'invalid_rows',
        'invitation_emails_sent',
        'rows_snapshot',
        'error_snapshot',
        'uploaded_at',
        'applied_at',
    ];

    protected $casts = [
        'uploaded_by_admin_id' => 'integer',
        'applied_by_admin_id' => 'integer',
        'total_rows' => 'integer',
        'saved_rows' => 'integer',
        'created_rows' => 'integer',
        'updated_rows' => 'integer',
        'invalid_rows' => 'integer',
        'invitation_emails_sent' => 'integer',
        'rows_snapshot' => 'array',
        'error_snapshot' => 'array',
        'uploaded_at' => 'datetime',
        'applied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
