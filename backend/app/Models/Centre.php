<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Centre extends Model
{
    use CrudTrait;
    use HasFactory;


    protected $fillable = [
        'title',
        'branch_id',
        'constituency_id',
        'status',

        'gps_address',
        'is_pwd_friendly',
        'wheelchair_accessible',
        'has_access_ramp',
        'has_accessible_toilet',
        'has_elevator',
        'supports_hearing_impaired',
        'supports_visually_impaired',
        'staff_trained_for_pwd',
        'accessibility_rating',
        'pwd_notes',
    ];


    protected $casts = [
        'constituency_id' => 'integer',
        'status' => 'boolean',
        'is_pwd_friendly' => 'boolean',
        'wheelchair_accessible' => 'boolean',
        'has_access_ramp' => 'boolean',
        'has_accessible_toilet' => 'boolean',
        'has_elevator' => 'boolean',
        'supports_hearing_impaired' => 'boolean',
        'supports_visually_impaired' => 'boolean',
        'staff_trained_for_pwd' => 'boolean',
        'gps_address' => 'string',
        'accessibility_rating' => 'integer',
        'pwd_notes' => 'string',
    ];


    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function constituency()
    {
        return $this->belongsTo(Constituency::class, 'constituency_id', 'id');
    }

    public function programme()
    {
        return $this->belongsToMany(Programme::class, 'courses');
    }

    public function districts()
    {
        return $this->belongsToMany(District::class, 'district_centre', 'centre_id', 'district_id')
            ->withTimestamps();
    }

}
