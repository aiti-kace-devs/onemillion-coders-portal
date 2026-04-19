<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'priority',
        'type',
        'target_type',
        'target_selection',
        'target_branches',
        'target_districts',
        'target_centres',
        'target_courses',
        'target_programme_batches',
        'target_master_sessions',
        'target_course_sessions',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'target_type' => 'string',
        'target_selection' => 'array',
        'target_branches' => 'array',
        'target_districts' => 'array',
        'target_centres' => 'array',
        'target_courses' => 'array',
        'target_programme_batches' => 'array',
        'target_master_sessions' => 'array',
        'target_course_sessions' => 'array',
        'sent_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function sendCampaignButton($crud = false)
    {
        if ($this->sent_at) {
            return '<span class="badge badge-success">Sent</span>';
        }

        return '<form action="' . route('campaign.send', $this->id) . '" method="POST" style="display:inline;">' .
               csrf_field() .
               '<button type="submit" class="btn btn-sm btn-success" onclick="return confirm(\'Send this campaign now?\'">Send Campaign</button>' .
               '</form>';
    }
}
