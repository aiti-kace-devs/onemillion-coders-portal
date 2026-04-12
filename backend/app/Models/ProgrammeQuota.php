<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgrammeQuota extends Model
{
    public const SCOPE_NATIONWIDE = 'nationwide';

    public const SCOPE_PER_CENTRE = 'per_centre';

    protected $fillable = [
        'programme_id',
        'batch_id',
        'centre_id',
        'scope',
        'quota_key',
        'max_enrollments',
    ];

    protected $casts = [
        'max_enrollments' => 'integer',
    ];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(Centre::class);
    }
}
