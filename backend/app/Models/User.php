<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Notification;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use CrudTrait;
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity, CausesActivity;

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
        'password',
        'userId',
        'card_type',
        'ghcard',
        'gender',
        'network_type',
        'has_disability',
        'registered_course',
        'shortlist',
        'student_level',
        'data',
        'support'
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => 'boolean',
        'has_disability' => 'boolean',
        'data' => 'array',
        'support' => 'boolean',
    ];



    protected static function booted()
    {
        static::updated(function ($user) {
            if ($user->wasChanged('shortlist') && $user->shortlist) {
                \App\Models\UserAdmission::updateOrCreate(
                    ['user_id' => $user->userId],
                    [
                        'course_id' => $user->registered_course,
                        'session' => null,
                        'confirmed' => null,
                        'location' => null,
                        'email_sent' => null
                    ]
                );
            }
        });
    }

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

    /**
     * Get the user's admissions
     */
    public function admissions()
    {
        return $this->hasMany(UserAdmission::class, 'user_id', 'userId');
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

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'user_id', 'userId');
    }

    public function hasAttendance()
    {
        return $this->hasMany(Attendance::class, 'user_id', 'userId');
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

    public function examEligibilityStatus($exam_id): array
    {
        $user_exam = user_exam::where('exam_id', $exam_id)
            ->where('user_id', $this->id)
            ->get()
            ->first();

        if ($user_exam?->submitted) {
            return [
                'status' => false,
                'message' => "Test already submitted on this exam. Submission Date:  $user_exam->submitted",
            ];
        }

        $exam = Oex_exam_master::where('id', $exam_id)->get()->first();
        if (now()->isAfter($exam->exam_date)) {
            return [
                'status' => false,
                'message' => "Unable to take exam. Exam deadline was  {$exam->exam_date->format(config('app.fulldate_format'))}",
            ];
        }

        $userCreatedAtPlusDeadlineDays = $this->created_at->addDays(config(EXAM_DEADLINE_AFTER_REGISTRATION, 7));


        if ($userCreatedAtPlusDeadlineDays->isBefore(now())) {
            return [
                'status' => false,
                'message' => 'Unable to take exam. Time to take exams has elapsed',
            ];
        }

        $usedTime = 0;

        if ($user_exam?->started) {
            $usedTime = now()->diffInMinutes($user_exam->started);
        }

        if ($usedTime > $exam->exam_duration) {
            // time elapsed update exam status
            $user_exam->submitted = now();
            $user_exam->update();

            return [
                'status' => false,
                'message' => "Unable to take exam. Exam duration time has elapsed.  $usedTime  minutes has passed since user started exams",
                'usedTime' => $usedTime,
            ];
        }

        return [
            'status' => true,
            'message' => 'true',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('student')
            ->setDescriptionForEvent(fn(string $event) => "Student {$event}")
            ->dontLogIfAttributesChangedOnly(['last_login']);
    }
}
