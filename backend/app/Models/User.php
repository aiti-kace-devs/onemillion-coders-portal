<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use CrudTrait;
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';


    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'exam',
        'status',
        'mobile_no',
        'age',
        'password',
        'userId',
        'ghcard',
        'gender',
        'network_type',
        'registered_course',
        'shortlist'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'shortlist' => 'boolean',
    ];



    public function course()
    {
        return $this->belongsTo(Course::class, 'registered_course');
    }


    public function isAdmitted()
    {
        return UserAdmission::where('user_id', $this->userId)
            ->whereNotNull('confirmed')->count() == 1;
    }

    public function hasAdmission()
    {
        return UserAdmission::where('user_id', $this->userId)
            ->count() == 1;
    }

    public function admissionEmailSent()
    {
        return UserAdmission::where('user_id', $this->userId)
            ->whereNotNull('email_sent')->count() == 1;
    }

    public function detailsUpdated()
    {
        return $this->details_updated_at != null;
    }

    public function isSuper()
    {
        return $this->is_super;
    }

    public function formResponse()
    {
        return $this->belongsTo(FormResponse::class, 'form_response_id');
    }

    public function admission()
    {
        return $this->hasOne(UserAdmission::class, 'user_id', 'userId');
    }

    public function rejectedAdmissions()
    {
        return $this->hasMany(AdmissionRejection::class, 'user_id', 'userId');
    }

    public function admissions()
    {
        return $this->hasMany(UserAdmission::class, 'user_id', 'userId');
    }

    public function userExams()
    {
        return $this->hasMany(\App\Models\user_exam::class, 'user_id', 'id');
    }

    public function examResults()
    {
        return $this->hasMany(\App\Models\Oex_result::class, 'user_id', 'id');
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function getNameWithEmail()
{
    return $this->name . ' (' . $this->email . ')';
}

}
