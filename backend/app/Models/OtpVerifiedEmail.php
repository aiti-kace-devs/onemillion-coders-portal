<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerifiedEmail extends Model
{
    protected $table = 'otp_verified_emails';

    protected $fillable = [
        'email',
        'otp_code_hash',
        'expires_at',
        'verified_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'verified_at' => 'datetime',
        'used_at'     => 'datetime',
    ];
}
