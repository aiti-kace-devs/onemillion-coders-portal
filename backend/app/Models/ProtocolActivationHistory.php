<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProtocolActivationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'protocol_list_id',
        'protocol_import_batch_id',
        'user_id',
        'user_uuid',
        'first_name',
        'middle_name',
        'last_name',
        'previous_name',
        'email',
        'gender',
        'age',
        'mobile_no',
        'ghcard',
        'invite_token_hash',
        'invitation_email_sent_at',
        'invitation_email_status',
        'invitation_email_queued_at',
        'invitation_email_last_attempt_at',
        'invitation_email_failed_at',
        'invitation_email_attempts',
        'invitation_email_failure_message',
        'activation_link_opened_at',
        'activation_completed_at',
        'failed_activation_attempts',
        'activated_ip_address',
    ];

    protected $casts = [
        'protocol_list_id' => 'integer',
        'protocol_import_batch_id' => 'integer',
        'user_id' => 'integer',
        'age' => 'integer',
        'invitation_email_attempts' => 'integer',
        'failed_activation_attempts' => 'integer',
        'invitation_email_sent_at' => 'datetime',
        'invitation_email_queued_at' => 'datetime',
        'invitation_email_last_attempt_at' => 'datetime',
        'invitation_email_failed_at' => 'datetime',
        'activation_link_opened_at' => 'datetime',
        'activation_completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])));
    }
}
