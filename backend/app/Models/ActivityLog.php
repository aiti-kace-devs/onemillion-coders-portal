<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class ActivityLog extends SpatieActivity
{
    use CrudTrait;

    protected $table = 'activity_log';
}
