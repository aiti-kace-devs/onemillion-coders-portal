<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerProgressSyncAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_code',
        'context',
        'omcp_id',
        'reason',
        'payload_json',
    ];

    protected $casts = [
        'payload_json' => 'array',
    ];
}
