<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
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
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'exam',
        'status',
        'mobile_no',
        'age',
        'age',
        'password',
        'userId',
        'card_type',
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



    /**
     * Set the password attribute with a double-hashing guard.
     */
    public function setPasswordAttribute($value)
    {
        if (empty($value)) {
            return;
        }

        // If the value is already a hash, set it directly without re-hashing
        if (Hash::info($value)['algoName'] !== 'unknown') {
            $this->attributes['password'] = $value;
        } else {
            // If it's plain text, hash it
            $this->attributes['password'] = Hash::make($value);
        }
    }



    public function course()
    {
        // Link the user to a course using the registered_course column as FK
        // users.registered_course -> courses.id
        return $this->belongsTo(Course::class, 'registered_course', 'id');
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


    public function questionnaire_response()
    {
        return $this->hasMany(QuestionnaireResponse::class);
    }

    public function hasAttendance()
    {
        return Attendance::where('user_id', $this->userId)->count() > 0;
    }

    public function getNameWithEmail()
    {
        return $this->name . ' (' . $this->email . ')';
    }

    /**
     * Get full name from separate fields or fallback to name field
     */
    public function getFullNameAttribute()
    {
        $parts = array_filter([$this->first_name, $this->middle_name, $this->last_name]);
        return !empty($parts) ? implode(' ', $parts) : $this->name;
    }

    /**
     * Set name from separate fields
     */
    public function setNameFromFields()
    {
        $this->name = $this->getFullNameAttribute();
    }
}
