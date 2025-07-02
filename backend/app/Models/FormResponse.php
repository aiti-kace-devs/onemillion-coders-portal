<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FormResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'response_data',
        'status'
    ];

    protected $casts = [
        'response_data' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }


    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function user()
    {
        return $this->hasOne(User::class, 'form_response_id');
    }
}
