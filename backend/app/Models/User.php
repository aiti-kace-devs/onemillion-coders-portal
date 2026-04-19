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
        'pwd',
        'registered_course',
        'shortlist',
        'last_login',
        'student_level',
        'data',
        'support',
        'student_id',
        'is_verification_blocked',
        'verification_block_reason',
        'verification_block_message',
        'verification_attempts_reset_at',
        'is_nia_syncing',
        'middle_name'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'shortlist' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => 'boolean',
        'pwd' => 'boolean',
        'data' => 'array',
        'support' => 'boolean',
        'is_verification_blocked' => 'boolean',
        'verification_attempts_reset_at' => 'datetime',
    ];

    /**
     * Backwards-compatible alias for code paths that still use "shortlisted".
     */
    public function setShortlistedAttribute($value): void
    {
        $this->attributes['shortlist'] = $value;
    }

    /**
     * Backwards-compatible alias for code paths that still read "shortlisted".
     */
    public function getShortlistedAttribute()
    {
        return $this->attributes['shortlist'] ?? null;
    }



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
                        'email_sent' => null
                    ]
                );
            }
        });

        static::updating(function ($user) {
            // Prevent updates to NIA verified fields if the user is already verified
            // and this is not a system sync.
            if ($user->isVerifiedByGhanaCard() && !($user->is_nia_syncing ?? false)) {
                $protectedFields = ['name', 'first_name', 'last_name'];

                foreach ($protectedFields as $field) {
                    if ($user->isDirty($field)) {
                        $user->{$field} = $user->getOriginal($field);
                        Log::warning("Blocked manual update to verified field: {$field}", ['user_id' => $user->id]);
                    }
                }

                // Protect date_of_birth in the 'data' JSON column
                if ($user->isDirty('data')) {
                    $originalData = $user->getOriginal('data');
                    $newData = $user->data;

                    if (isset($originalData['date_of_birth']) && isset($newData['date_of_birth'])) {
                        if ($originalData['date_of_birth'] !== $newData['date_of_birth']) {
                            $newData['date_of_birth'] = $originalData['date_of_birth'];
                            $user->data = $newData;
                            Log::warning("Blocked manual update to verified date_of_birth in data column", ['user_id' => $user->id]);
                        }
                    }
                }
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

    /**
     * Set the email attribute - always store lowercase.
     */
    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = is_string($value) ? strtolower($value) : $value;
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

    public function getSelectedSessionAttribute()
    {
        $dates = $this->session_dates;
        $time = $this->session_time_value;

        return trim("{$dates} " . ($time ? "({$time})" : "")) ?: ($this->admission?->session ?? 'N/A');
    }

    public function getSessionDatesAttribute()
    {
        $admission = $this->admission;
        if (!$admission)
            return '';

        $batch = $admission->programmeBatch;
        if (!$batch)
            return '';

        $start = $batch->start_date?->format('jS M') ?? '';
        $end = $batch->end_date?->format('jS M') ?? '';

        return "{$start} - {$end}";
    }

    public function getSessionTimeValueAttribute()
    {
        $admission = $this->admission;
        if (!$admission)
            return '';

        $sessionRecord = $admission->courseSession;

        // If no direct session record, check for a booking record
        if (!$sessionRecord && $admission->booking) {
            $sessionRecord = $admission->booking->session;
        }

        // Try 'course_time' (CourseSession) or 'time' (MasterSession)
        return $sessionRecord?->course_time ?? $sessionRecord?->time ?? '';
    }

    public function getSessionNameAttribute()
    {
        $admission = $this->admission;
        if (!$admission)
            return '';

        $sessionRecord = $admission->courseSession;

        // If no direct session record, check for a booking record
        if (!$sessionRecord && $admission->booking) {
            $sessionRecord = $admission->booking->session;
        }

        // Try 'name' (CourseSession), 'master_name' (MasterSession), 'session' (fallback) or 'title' (legacy)
        return $sessionRecord?->name ?? $sessionRecord?->master_name ?? $sessionRecord?->session ?? $sessionRecord?->title ?? $admission->session ?? '';
    }

    /**
     * Get the validity period for the ID card
     */
    public function getValidityPeriodAttribute()
    {
        $admissionBatch = $this->admission?->programmeBatch?->admissionBatch;
        if (!$admissionBatch)
            return 'N/A';

        $start = \Carbon\Carbon::parse($admissionBatch->start_date)->format('M, Y');
        $end = \Carbon\Carbon::parse($admissionBatch->end_date)->format('M, Y');

        return trim("{$start} - {$end}") ?: 'N/A';
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

    // public function formResponse()
    // {
    //     return $this->belongsTo(FormResponse::class, 'form_response_id');
    // }

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

    public function hasAttendance(): bool
    {
        return $this->attendances()->exists();
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
            ->logExcept(['password'])
            ->logOnlyDirty()
            ->useLogName('student')
            ->setDescriptionForEvent(fn(string $event) => "Student {$event}")
            ->dontLogIfAttributesChangedOnly(['last_login']);
    }

    public function userAssessment()
    {
        return $this->hasOne(UserAssessment::class);
    }

    /**
     * Get the student's full name (alias for frontend consistency)
     */
    public function getStudentNameAttribute()
    {
        return $this->full_name;
    }

    /**
     * Get the name of the course the student is admitted to
     */
    public function getCourseNameAttribute()
    {
        return $this->admission?->course?->course_name;
    }


    /**
     * Get the date the admission was confirmed (used as verification date)
     */
    public function getVerificationDateAttribute()
    {
        return $this->admission?->confirmed;
    }

    public function ghanaCardVerifications()
    {
        return $this->hasMany(GhanaCardVerification::class);
    }

    public function latestGhanaCardVerification()
    {
        return $this->hasOne(GhanaCardVerification::class)->latestOfMany();
    }

    public function isVerifiedByGhanaCard(): bool
    {
        if ($this->is_verification_blocked) {
            return false;
        }

        return $this->ghanaCardVerifications()
            ->where('code', '00')
            ->where('verified', true)
            ->exists();
    }
}
