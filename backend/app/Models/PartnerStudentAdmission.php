<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerStudentAdmission extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'partner_id',
        'programme_id',
        'external_user_id',
        'enrollment_status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    public function getStatusBadgeAttribute()
    {
        return '<span class="badge badge-' . ($this->enrollment_status === 'enrolled' ? 'success' : 'warning') . '">' . ucfirst($this->enrollment_status) . '</span>';
    }

    public function isEnrolled()
    {
        return $this->enrollment_status === 'enrolled';
    }

    public function isAwaiting()
    {
        return $this->enrollment_status === 'awaiting';
    }

    public function isFailed()
    {
        return $this->enrollment_status === 'failed';
    }
}
