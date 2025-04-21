<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAdmission extends Model
{
    use HasFactory;

    protected $table = 'user_admission';

    protected $fillable = ['user_id', 'course_id', 'email_sent', 'session', 'location', 'confirmed'];
}
