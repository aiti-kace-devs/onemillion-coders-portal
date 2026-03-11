<?php

namespace App\Models;



use App\Http\Controllers\Traits\CustomTimestamps;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    /**
     * Convert the model to a Statamic user.
     *
     * @return \Statamic\Contracts\Auth\User
     */
    public function toStatamicUser()
    {
        return (new \Statamic\Auth\Eloquent\User)->model($this);
    }
    use CrudTrait;
    use HasApiTokens, HasFactory, Notifiable, HasRoles, CustomTimestamps, HasUuids;
    protected $guard = 'admin';

    protected $guard_name = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['name', 'email', 'password', 'status', 'course_id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'preferences' => 'json',
        'is_super' => 'boolean',
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


    public function isSuper()
    {
        return $this->is_super;
    }

    /**
     * Return course IDs explicitly assigned to this admin.
     */
    public function assignedCourseIds(): array
    {
        return $this->assignedCourses()
            ->pluck('courses.id')
            ->map(fn ($courseId) => (int) $courseId)
            ->all();
    }

    /**
     * Return visible course IDs for this admin.
     * `null` means unrestricted visibility (super admin).
     */
    public function visibleCourseIds(): ?array
    {
        if ($this->isSuper()) {
            return null;
        }

        return $this->assignedCourseIds();
    }

    public function assignedCourses()
    {
        return $this->belongsToMany(Course::class, 'admin_course', 'admin_id', 'course_id')
            ->select(['courses.id', 'courses.course_name', 'courses.centre_id', 'courses.duration', 'courses.status'])
            ->withTimestamps();
    }

    public function courses()
    {
        return $this->assignedCourses();
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
