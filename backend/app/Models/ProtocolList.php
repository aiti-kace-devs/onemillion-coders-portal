<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProtocolList extends Model
{
    use HasFactory;

    public const GH_CARD_REGEX = '/^GHA-\d{9}-\d$/';

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'previous_name',
        'email',
        'gender',
        'age',
        'mobile_no',
        'ghcard',
        'protocol_import_batch_id',
        'email_change_attempts',
        'ghcard_change_attempts',
        'activation_email_sent_at',
        'invitation_email_status',
        'invitation_email_queued_at',
        'invitation_email_last_attempt_at',
        'invitation_email_failed_at',
        'invitation_email_attempts',
        'invitation_email_failure_message',
        'invite_token_hash',
        'invite_token_issued_at',
        'activation_link_opened_at',
        'activation_session_token_hash',
        'activation_session_expires_at',
        'failed_activation_attempts',
    ];

    protected $casts = [
        'age' => 'integer',
        'protocol_import_batch_id' => 'integer',
        'email_change_attempts' => 'integer',
        'ghcard_change_attempts' => 'integer',
        'failed_activation_attempts' => 'integer',
        'invitation_email_attempts' => 'integer',
        'activation_email_sent_at' => 'datetime',
        'invitation_email_queued_at' => 'datetime',
        'invitation_email_last_attempt_at' => 'datetime',
        'invitation_email_failed_at' => 'datetime',
        'invite_token_issued_at' => 'datetime',
        'activation_link_opened_at' => 'datetime',
        'activation_session_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = is_string($value) ? strtolower(trim($value)) : $value;
    }

    public function setGenderAttribute($value): void
    {
        $gender = strtolower(trim((string) $value));
        $this->attributes['gender'] = in_array($gender, ['m', 'male'], true)
            ? 'male'
            : (in_array($gender, ['f', 'female'], true) ? 'female' : $gender);
    }

    public function setGhcardAttribute($value): void
    {
        $this->attributes['ghcard'] = static::normalizeGhcard($value);
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])));
    }

    public static function normalizeGhcard(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtoupper(trim($value));
        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);
        if (strlen((string) $digits) === 10) {
            return sprintf('GHA-%s-%s', substr($digits, 0, 9), substr($digits, 9, 1));
        }

        return $value;
    }

    public static function isValidGhcard(?string $value): bool
    {
        return is_string($value) && preg_match(self::GH_CARD_REGEX, $value) === 1;
    }

    public function importBatch()
    {
        return $this->belongsTo(ProtocolImportBatch::class, 'protocol_import_batch_id');
    }
}
