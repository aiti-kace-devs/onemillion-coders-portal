<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'title',
        'status'
    ];


    protected $casts = [
        'status' => 'boolean',
    ];

    public function getLocationAttribute(): string
    {
        return $this->title ?? '';
    }

    public function centre()
    {
        return $this->hasMany(Centre::class);
    }

    public function districts()
    {
        return $this->hasMany(District::class, 'branch_id', 'id');
    }

    protected static function booted()
    {
        static::saved(function ($branch) {
            if ($branch->wasChanged('title')) {
                $branch->centre->each(function ($centre) {
                    $centre->courses()->get()->each->save();
                });
            }
        });
    }
}
